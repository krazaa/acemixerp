<?php

namespace Modules\Sales\Http\Controllers;

use App\Contracts\CustomerManager;
use App\Contracts\PaymentTermManager;
use App\Contracts\WarehouseManager;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Organization;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Hr\Models\Employee;
use Modules\Sales\Contracts\SalesInvoiceManager;
use Modules\Sales\Data\SalesInvoiceData;
use Modules\Sales\Enums\SalesInvoiceStatus;
use Modules\Sales\Http\Requests\SalesInvoices\ApproveSalesInvoiceRequest;
use Modules\Sales\Http\Requests\SalesInvoices\StoreSalesInvoiceRequest;
use Modules\Sales\Http\Requests\SalesInvoices\UpdateSalesInvoiceRequest;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesOrder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesInvoiceController extends Controller
{
    public function __construct(
        private readonly SalesInvoiceManager $invoices,
        private readonly CustomerManager $customers,
        private readonly WarehouseManager $warehouses,
        private readonly PaymentTermManager $paymentTerms,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalesInvoice::class);

        return view('sales::sales-invoices.index', [
            'invoices' => $this->invoices->paginate(
                $request->only(['search', 'status', 'customer_id', 'from', 'to'])
            ),
            'customers' => $this->customers->allActive(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', SalesInvoice::class);

        $soId = $request->integer('sales_order_id');
        $order = $soId
            ? SalesOrder::query()->with(['lines.item', 'lines.unit', 'customer', 'paymentTerm'])
                ->whereNotIn('status', ['delivered', 'closed', 'canceled', 'rejected'])
                ->findOrFail($soId)
            : null;
        $customerId = $request->integer('customer_id');
        $customer = ! $order && $customerId
            ? Customer::query()->active()->findOrFail($customerId)
            : null;

        return view('sales::sales-invoices.create', $this->formDependencies() + [
            'invoice' => new SalesInvoice([
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'currency_code' => $order?->currency_code ?? $customer?->currency_code ?? Organization::current()->currency_code,
                'customer_id' => $order?->customer_id ?? $customer?->id,
                'sales_order_id' => $order?->id,
                'payment_term_id' => $order?->payment_term_id ?? $customer?->payment_term_id,
                'status' => SalesInvoiceStatus::Draft,
            ]),
            'salesOrder' => $order,
            'openOrders' => SalesOrder::query()
                ->whereIn('status', ['delivered', 'partially_delivered'])
                ->with('customer:id,name')
                ->orderByDesc('order_date')
                ->get(['id', 'number', 'customer_id', 'currency_code']),
            'salespersons' => Employee::query()
                ->where('status', ['active'])
                ->orderBy('number')
                ->get(['id', 'number', 'first_name', 'last_name']),
        ]);
    }

    public function store(StoreSalesInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoices->create(
            SalesInvoiceData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()->route('sales.sales-invoices.show', $invoice)
            ->with('status', "Invoice {$invoice->number} created as draft.");
    }

    public function show(SalesInvoice $salesInvoice): View
    {
        $this->authorize('view', $salesInvoice);

        return view('sales::sales-invoices.show', [
            'invoice' => $salesInvoice->load([
                'lines.item', 'lines.unit',
                'customer', 'salesOrder', 'warehouse',
                'journalEntry', 'allocations.allocator', 'allocations.payment',
                'reverses', 'reversedBy', 'returns.creditNote',
                'creator', 'approver', 'poster',
            ]),
        ]);
    }

    public function print(SalesInvoice $salesInvoice): View
    {
        $this->authorize('view', $salesInvoice);

        $company = Organization::current();

        $logoBase64 = null;

        if ($company?->logo_path) {
            $path = ltrim($company->logo_path, '/');

            if (Storage::disk('local')->exists($path)) {
                $mimeType = Storage::disk('local')->mimeType($path);

                $logoBase64 = 'data:'.$mimeType.';base64,'.
                    base64_encode(
                        Storage::disk('local')->get($path)
                    );
            }
        }

        return view('sales::sales-invoices.print', [
            'invoice' => $salesInvoice->load([
                'lines.item',
                'lines.unit',
                'customer',
                'salesOrder',
                'warehouse',
            ]),
            'company' => $company,
            'logoBase64' => $logoBase64,
        ]);
    }

    public function csv(SalesInvoice $salesInvoice): StreamedResponse
    {
        $this->authorize('view', $salesInvoice);
        $invoice = $salesInvoice->load(['lines.item', 'lines.unit', 'customer']);

        return response()->streamDownload(function () use ($invoice): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Invoice', $invoice->number]);
            fputcsv($handle, ['Customer', $invoice->customer?->name]);
            fputcsv($handle, ['Invoice Date', $invoice->invoice_date->format('Y-m-d')]);
            fputcsv($handle, ['Due Date', $invoice->due_date->format('Y-m-d')]);
            fputcsv($handle, []);
            fputcsv($handle, ['#', 'Item Code', 'Item', 'Unit', 'Quantity', 'Unit Price', 'Discount %', 'GST %', 'WHT %', 'Line Total']);
            foreach ($invoice->lines as $index => $line) {
                fputcsv($handle, [$index + 1, $line->item?->code, $line->item?->name, $line->unit?->code, $line->quantity, $line->unit_price, $line->discount_percent, $line->tax_rate, $line->wht_tax_rate, $line->line_total]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Subtotal', $invoice->subtotal]);
            fputcsv($handle, ['Tax', $invoice->tax_total]);
            fputcsv($handle, ['Total', $invoice->total]);
        }, "{$invoice->number}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function edit(SalesInvoice $salesInvoice): View
    {
        $this->authorize('update', $salesInvoice);

        return view('sales::sales-invoices.edit', $this->formDependencies() + [
            'invoice' => $salesInvoice->load('lines'),
            'salesOrder' => $salesInvoice->salesOrder?->load('lines.item'),
            'openOrders' => collect(),
            'salespersons' => Employee::query()
                ->where('status', ['active'])
                ->orderBy('number')
                ->get(['id', 'number', 'first_name', 'last_name']),
        ]);
    }

    public function update(UpdateSalesInvoiceRequest $request, SalesInvoice $salesInvoice): RedirectResponse
    {
        $this->invoices->update($salesInvoice, SalesInvoiceData::fromRequest($request), $request->user()->id);

        return redirect()->route('sales.sales-invoices.show', $salesInvoice)
            ->with('status', 'Invoice updated.');
    }

    public function destroy(SalesInvoice $salesInvoice): RedirectResponse
    {
        $this->authorize('delete', $salesInvoice);
        $this->invoices->delete($salesInvoice);

        return redirect()->route('sales.sales-invoices.index')->with('status', 'Invoice deleted.');
    }

    public function match(SalesInvoice $salesInvoice): RedirectResponse
    {
        $this->authorize('match', $salesInvoice);
        $invoice = $this->invoices->match($salesInvoice, auth()->id());
        $level = $invoice->match_status === 'matched' ? 'success' : 'warning';

        return back()->with('status', "Match result: {$invoice->match_status}.")
            ->with('status_level', $level);
    }

    public function approve(ApproveSalesInvoiceRequest $request, SalesInvoice $salesInvoice): RedirectResponse
    {
        $override = $request->boolean('override_mismatch');
        if ($override) {
            $this->authorize('overrideMismatch', $salesInvoice);
        }
        $this->invoices->approve($salesInvoice, auth()->id(), $override);

        return back()->with('status', 'Invoice approved.');
    }

    public function reject(Request $request, SalesInvoice $salesInvoice): RedirectResponse
    {
        $this->authorize('reject', $salesInvoice);
        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        $this->invoices->reject($salesInvoice, auth()->id(), $request->string('reason')->toString());

        return back()->with('status', 'Invoice rejected.');
    }

    public function post(SalesInvoice $salesInvoice): RedirectResponse
    {
        $this->authorize('post', $salesInvoice);
        $invoice = $this->invoices->post($salesInvoice, auth()->id());

        return back()->with(
            'status',
            "Invoice {$invoice->number} posted to GL as entry ".($invoice->journalEntry?->number ?? '—').'.',
        );
    }

    public function cancel(Request $request, SalesInvoice $salesInvoice): RedirectResponse
    {
        $this->authorize('cancel', $salesInvoice);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->invoices->cancel($salesInvoice, auth()->id(), $request->input('reason'));

        return back()->with('status', 'Invoice cancelled.');
    }

    public function reverse(Request $request, SalesInvoice $salesInvoice): RedirectResponse
    {
        $this->authorize('reverse', $salesInvoice);
        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        $reversal = $this->invoices->reverse($salesInvoice, auth()->id(), $request->string('reason')->toString());

        return redirect()->route('sales.sales-invoices.show', $reversal)
            ->with('status', "Reversal {$reversal->number} created and posted.");
    }

    /** @return array<string, mixed> */
    private function formDependencies(): array
    {
        return [
            'customers' => $this->customers->allActive(),
            'warehouses' => $this->warehouses->allActive(),
            'paymentTerms' => $this->paymentTerms->allActive(),
            'items' => Item::query()
                ->where('is_sellable', true)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'selling_price', 'tax_rate_id']),
            'units' => Unit::query()
                ->where('status', 'active')
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ];
    }
}
