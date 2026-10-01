<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use App\Contracts\BankAccountManager;
use App\Contracts\CustomerManager;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Sales\Contracts\CustomerReceiptManager;
use Modules\Sales\Data\CustomerReceiptData;
use Modules\Sales\Enums\CustomerReceiptStatus;
use Modules\Sales\Enums\SalesInvoiceStatus;
use Modules\Sales\Http\Requests\CustomerReceipts\StoreCustomerReceiptRequest;
use Modules\Sales\Http\Requests\CustomerReceipts\UpdateCustomerReceiptRequest;
use Modules\Sales\Models\CustomerReceipt;
use Modules\Sales\Models\SalesInvoice;

class CustomerReceiptController extends Controller
{
    public function __construct(
        private readonly CustomerReceiptManager $receipts,
        private readonly CustomerManager $customers,
        private readonly BankAccountManager $bankAccounts,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CustomerReceipt::class);

        return view('sales::customer-receipts.index', [
            'receipts' => $this->receipts->paginate(
                $request->only(['search', 'status', 'customer_id', 'method', 'from', 'to'])
            ),
            'customers' => $this->customers->allActive(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', CustomerReceipt::class);

        $customerId = $request->integer('customer_id');
        $customer = $customerId ? Customer::query()->findOrFail($customerId) : null;

        return view('sales::customer-receipts.create', [
            'receipt' => new CustomerReceipt([
                'receipt_date' => now()->toDateString(),
                'currency_code' => $customer?->currency_code
                    ?? Organization::current()->currency_code,
                'customer_id' => $customer?->id,
                'payment_method' => 'bank_transfer',
                'status' => CustomerReceiptStatus::Draft,
            ]),
            'customers' => $this->customers->allActive(),
            'bankAccounts' => $this->bankAccounts->allActive(),
            'customer' => $customer,
            'outstandingInvoices' => $customer ? $this->outstandingFor($customer) : collect(),
        ]);
    }

    public function store(StoreCustomerReceiptRequest $request): RedirectResponse
    {
        $receipt = $this->receipts->create(
            CustomerReceiptData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()->route('sales.customer-receipts.show', $receipt)
            ->with('status', "Receipt {$receipt->number} created as draft.");
    }

    public function show(CustomerReceipt $customerReceipt): View
    {
        $this->authorize('view', $customerReceipt);

        return view('sales::customer-receipts.show', [
            'receipt' => $customerReceipt->load([
                'customer', 'bankAccount', 'journalEntry',
                'allocations.allocatable', 'allocations.allocator',
                'submitter', 'approver', 'poster',
                'reverses', 'reversedBy',
            ]),
        ]);
    }

    public function edit(CustomerReceipt $customerReceipt): View
    {
        $this->authorize('update', $customerReceipt);

        return view('sales::customer-receipts.edit', [
            'receipt' => $customerReceipt->load('allocations.allocatable'),
            'customers' => $this->customers->allActive(),
            'bankAccounts' => $this->bankAccounts->allActive(),
            'customer' => $customerReceipt->customer,
            'outstandingInvoices' => $this->outstandingFor($customerReceipt->customer),
        ]);
    }

    public function update(UpdateCustomerReceiptRequest $request, CustomerReceipt $customerReceipt): RedirectResponse
    {
        $this->receipts->update($customerReceipt, CustomerReceiptData::fromRequest($request), $request->user()->id);

        return redirect()->route('sales.customer-receipts.show', $customerReceipt)
            ->with('status', 'Receipt updated.');
    }

    public function destroy(CustomerReceipt $customerReceipt): RedirectResponse
    {
        $this->authorize('delete', $customerReceipt);
        $this->receipts->delete($customerReceipt);

        return redirect()->route('sales.customer-receipts.index')->with('status', 'Receipt deleted.');
    }

    public function submit(CustomerReceipt $customerReceipt): RedirectResponse
    {
        $this->authorize('submit', $customerReceipt);
        $this->receipts->submit($customerReceipt, auth()->id());

        return back()->with('status', 'Receipt submitted.');
    }

    public function approve(CustomerReceipt $customerReceipt): RedirectResponse
    {
        $this->authorize('approve', $customerReceipt);
        $this->receipts->approve($customerReceipt, auth()->id());

        return back()->with('status', 'Receipt approved.');
    }

    public function reject(Request $request, CustomerReceipt $customerReceipt): RedirectResponse
    {
        $this->authorize('reject', $customerReceipt);
        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        $this->receipts->reject($customerReceipt, auth()->id(), $request->string('reason')->toString());

        return back()->with('status', 'Receipt rejected.');
    }

    public function post(CustomerReceipt $customerReceipt): RedirectResponse
    {
        $this->authorize('post', $customerReceipt);
        $receipt = $this->receipts->post($customerReceipt, auth()->id());

        return back()->with(
            'status',
            "Receipt {$receipt->number} posted to GL as entry ".($receipt->journalEntry?->number ?? '—').'.',
        );
    }

    public function cancel(Request $request, CustomerReceipt $customerReceipt): RedirectResponse
    {
        $this->authorize('cancel', $customerReceipt);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->receipts->cancel($customerReceipt, auth()->id(), $request->input('reason'));

        return back()->with('status', 'Receipt cancelled.');
    }

    public function reverse(Request $request, CustomerReceipt $customerReceipt): RedirectResponse
    {
        $this->authorize('reverse', $customerReceipt);
        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        $reversal = $this->receipts->reverse($customerReceipt, auth()->id(), $request->string('reason')->toString());

        return redirect()->route('sales.customer-receipts.show', $reversal)
            ->with('status', "Reversal {$reversal->number} created.");
    }

    /** JSON feed for the allocation grid */
    public function outstandingInvoices(Customer $customer): JsonResponse
    {
        $this->authorize('viewAny', CustomerReceipt::class);

        return response()->json([
            'data' => $this->outstandingFor($customer)
                ->map(fn (SalesInvoice $i) => [
                    'id' => $i->id,
                    'number' => $i->number,
                    'invoice_date' => $i->invoice_date->format('Y-m-d'),
                    'due_date' => $i->due_date->format('Y-m-d'),
                    'currency_code' => $i->currency_code,
                    'total' => (string) $i->total,
                    'paid_amount' => (string) $i->paid_amount,
                    'outstanding' => $i->outstanding(),
                    'sales_order' => $i->salesOrder?->number,
                ])
                ->values(),
        ]);
    }

    private function outstandingFor(Customer $customer)
    {
        return SalesInvoice::query()
            ->with('salesOrder:id,number')
            ->withSum(['creditNotes as posted_credit_total' => fn ($query) => $query->where('status', 'posted')], 'total')
            ->where('customer_id', $customer->id)
            ->whereIn('status', [
                SalesInvoiceStatus::Posted->value,
                SalesInvoiceStatus::PartiallyPaid->value,
            ])
            ->whereRaw('total - paid_amount > 0')
            ->orderBy('due_date')
            ->get()->filter(fn (SalesInvoice $invoice) => bccomp($invoice->outstanding(), '0', 4) > 0)->values();
    }
}
