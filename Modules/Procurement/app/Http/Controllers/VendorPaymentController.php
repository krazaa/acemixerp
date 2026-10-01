<?php

namespace Modules\Procurement\Http\Controllers;

use App\Contracts\BankAccountManager;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Procurement\Contracts\VendorPaymentManager;
use Modules\Procurement\Data\VendorPaymentData;
use Modules\Procurement\Enums\SupplierInvoiceStatus;
use Modules\Procurement\Enums\VendorPaymentStatus;
use Modules\Procurement\Http\Requests\VendorPayments\StoreVendorPaymentRequest;
use Modules\Procurement\Http\Requests\VendorPayments\UpdateVendorPaymentRequest;
use Modules\Procurement\Models\SupplierInvoice;
use Modules\Procurement\Models\VendorPayment;

class VendorPaymentController extends Controller
{
    public function __construct(
        private readonly VendorPaymentManager $payments,
        private readonly BankAccountManager $bankAccounts,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', VendorPayment::class);

        return view('procurement::vendor-payments.index', [
            'payments' => $this->payments->paginate(
                $request->only(['search', 'status', 'vendor_id', 'method', 'from', 'to'])
            ),
            'vendors' => Vendor::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', VendorPayment::class);

        $vendorId = $request->integer('vendor_id');
        $vendor = $vendorId ? Vendor::query()->findOrFail($vendorId) : null;

        // Pre-select the invoice to settle if the caller came from an invoice show page.
        $prefillInvoice = $request->integer('supplier_invoice_id')
            ? SupplierInvoice::query()
                ->with('purchaseOrder:id,number')
                ->find($request->integer('supplier_invoice_id'))
            : null;

        return view('procurement::vendor-payments.create', [
            'payment' => new VendorPayment([
                'payment_date' => now()->toDateString(),
                'billing_month' => now()->format('Y-m'),
                'currency_code' => $vendor?->currency_code
                    ?? $prefillInvoice?->currency_code
                    ?? Organization::current()->currency_code,
                'vendor_id' => $vendor?->id ?? $prefillInvoice?->vendor_id,
                'payment_method' => 'bank_transfer',
                'status' => VendorPaymentStatus::Draft,
            ]),
            'vendors' => Vendor::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'bankAccounts' => $this->bankAccounts->allActive(),
            'vendor' => $vendor,
            'prefillInvoice' => $prefillInvoice,
            'outstandingInvoices' => $vendor
                ? $this->outstandingForVendor($vendor)
                : collect(),
        ]);
    }

    public function store(StoreVendorPaymentRequest $request): RedirectResponse
    {
        $payment = $this->payments->create(
            VendorPaymentData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('procurement.vendor-payments.show', $payment)
            ->with('status', "Payment {$payment->number} created as draft.");
    }

    public function show(VendorPayment $vendorPayment): View
    {
        $this->authorize('view', $vendorPayment);

        return view('procurement::vendor-payments.show', [
            'payment' => $vendorPayment->load([
                'vendor',
                'bankAccount',
                'journalEntry',
                'allocations.allocatable',
                'allocations.allocator',
                'submitter',
                'approver',
                'poster',
                'creator',
                'updater',
                'reverses',
                'reversedBy',
            ]),
        ]);
    }

    public function voucher(VendorPayment $vendorPayment): View
    {
        $this->authorize('view', $vendorPayment);

        return view('procurement::vendor-payments.voucher', [
            'organization' => Organization::current(),
            'payment' => $vendorPayment->load([
                'vendor',
                'bankAccount',
                'journalEntry',
                'allocations.allocatable',
                'allocations.allocator',
                'poster',
            ]),
        ]);
    }

    public function edit(VendorPayment $vendorPayment): View
    {
        $this->authorize('update', $vendorPayment);

        return view('procurement::vendor-payments.edit', [
            'payment' => $vendorPayment->load('allocations.allocatable'),
            'vendors' => Vendor::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'bankAccounts' => $this->bankAccounts->allActive(),
            'vendor' => $vendorPayment->vendor,
            'outstandingInvoices' => $this->outstandingForVendor(
                $vendorPayment->vendor,
                excludingPayment: $vendorPayment,
            ),
        ]);
    }

    public function update(UpdateVendorPaymentRequest $request, VendorPayment $vendorPayment): RedirectResponse
    {
        $this->payments->update(
            $vendorPayment,
            VendorPaymentData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('procurement.vendor-payments.show', $vendorPayment)
            ->with('status', 'Payment updated.');
    }

    public function destroy(VendorPayment $vendorPayment): RedirectResponse
    {
        $this->authorize('delete', $vendorPayment);

        $this->payments->delete($vendorPayment);

        return redirect()
            ->route('procurement.vendor-payments.index')
            ->with('status', 'Payment deleted.');
    }

    public function submit(VendorPayment $vendorPayment): RedirectResponse
    {
        $this->authorize('submit', $vendorPayment);

        $this->payments->submit($vendorPayment, auth()->id());

        return back()->with('status', 'Payment submitted for approval.');
    }

    public function approve(VendorPayment $vendorPayment): RedirectResponse
    {
        $this->authorize('approve', $vendorPayment);

        $this->payments->approve($vendorPayment, auth()->id());

        return back()->with('status', 'Payment approved.');
    }

    public function reject(Request $request, VendorPayment $vendorPayment): RedirectResponse
    {
        $this->authorize('reject', $vendorPayment);

        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);

        $this->payments->reject(
            $vendorPayment,
            auth()->id(),
            $request->string('reason')->toString(),
        );

        return back()->with('status', 'Payment rejected.');
    }

    public function post(VendorPayment $vendorPayment): RedirectResponse
    {
        $this->authorize('post', $vendorPayment);

        $payment = $this->payments->post($vendorPayment, auth()->id());

        return back()->with(
            'status',
            "Payment {$payment->number} posted to GL as entry ".
            ($payment->journalEntry?->number ?? '—').'.',
        );
    }

    public function reverse(Request $request, VendorPayment $vendorPayment): RedirectResponse
    {
        $this->authorize('reverse', $vendorPayment);

        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);

        $reversal = $this->payments->reverse(
            $vendorPayment,
            auth()->id(),
            $request->string('reason')->toString(),
        );

        return redirect()
            ->route('procurement.vendor-payments.show', $reversal)
            ->with('status', "Reversal {$reversal->number} created. Submit, approve, and post it to complete the reversal.");
    }

    public function cancel(Request $request, VendorPayment $vendorPayment): RedirectResponse
    {
        $this->authorize('cancel', $vendorPayment);

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->payments->cancel(
            $vendorPayment,
            auth()->id(),
            $request->input('reason'),
        );

        return back()->with('status', 'Payment cancelled.');
    }

    /**
     * JSON feed for the payment form: the vendor's outstanding supplier invoices.
     * Used by the allocation grid to pre-populate.
     */
    public function outstandingInvoices(Vendor $vendor): JsonResponse
    {
        $this->authorize('viewAny', VendorPayment::class);

        $rows = $this->outstandingForVendor($vendor)
            ->map(fn (SupplierInvoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'vendor_invoice_number' => $invoice->vendor_invoice_number,
                'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'currency_code' => $invoice->currency_code,
                'total' => (string) $invoice->total,
                'paid_amount' => (string) $invoice->paid_amount,
                'outstanding' => $invoice->outstanding(),
                'purchase_order_number' => $invoice->purchaseOrder?->number,
            ])
            ->values();

        return response()->json(['data' => $rows]);
    }

    /**
     * @return Collection<int, SupplierInvoice>
     */
    private function outstandingForVendor(Vendor $vendor, ?VendorPayment $excludingPayment = null)
    {
        $query = SupplierInvoice::query()
            ->with('purchaseOrder:id,number')
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', [
                SupplierInvoiceStatus::Posted->value,
                SupplierInvoiceStatus::PartiallyPaid->value,
            ])
            ->whereRaw('total - paid_amount > 0')
            ->orderBy('invoice_date');

        return $query->get();
    }
}
