<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Organization;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Models\Brand;
use Modules\Procurement\Contracts\SupplierInvoiceManager;
use Modules\Procurement\Data\SupplierInvoiceData;
use Modules\Procurement\Enums\SupplierInvoiceStatus;
use Modules\Procurement\Http\Requests\SupplierInvoices\ApproveSupplierInvoiceRequest;
use Modules\Procurement\Http\Requests\SupplierInvoices\StoreSupplierInvoiceRequest;
use Modules\Procurement\Http\Requests\SupplierInvoices\UpdateSupplierInvoiceRequest;
use Modules\Procurement\Models\PurchaseOrder;
use Modules\Procurement\Models\SupplierInvoice;

class SupplierInvoiceController extends Controller
{
    public function __construct(private readonly SupplierInvoiceManager $invoices) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SupplierInvoice::class);

        return view('procurement::supplier-invoices.index', [
            'invoices' => $this->invoices->paginate(
                $request->only(['search', 'status', 'vendor_id', 'from', 'to'])
            ),
            'vendors' => Vendor::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', SupplierInvoice::class);

        $poId = $request->integer('purchase_order_id');
        if ($request->boolean('standalone')) {
            return view('procurement::supplier-invoices.standalone', [
                'brands' => Brand::query()->orderBy('name')->get(),
                'invoice' => new SupplierInvoice([
                    'invoice_date' => now()->toDateString(),
                    'due_date' => now()->addDays(30)->toDateString(),
                    'currency_code' => Organization::current()->currency_code,
                ]),
                'vendors' => Vendor::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
                'expenseAccounts' => Account::query()->active()->postable()->ofType(AccountType::Expense)->orderBy('code')->get(['id', 'code', 'name']),
            ]);
        }
        $po = $poId
            ? PurchaseOrder::query()
                ->with(['lines.brand', 'lines.item', 'lines.unit', 'vendor'])
                ->findOrFail($poId)
            : null;

        $prefilledLines = [];

        if ($po) {
            $prefilledLines = $po->lines
                ->map(function ($line, int $i) {
                    // Only lines with an accepted receipt and remaining invoicable quantity.
                    $accepted = (string) $line->received_quantity;
                    $alreadyInvoiced = (string) $line->invoiced_quantity;
                    $open = bcsub($accepted, $alreadyInvoiced, 4);

                    return [
                        'purchase_order_line_id' => $line->id,
                        'item_id' => $line->item_id,
                        'brand_id' => $line->brand_id,
                        'quantity' => bccomp($open, '0', 4) > 0 ? $open : '0.0000',
                        'unit_price' => (string) $line->unit_price,
                        'tax_rate' => (string) $line->tax_rate,
                        'wht_rate' => '0.000000',
                        'description' => null,
                        'purchase_order_line' => $line,
                    ];
                })
                ->filter(fn ($row) => bccomp($row['quantity'], '0', 4) > 0)
                ->values()
                ->all();
        }

        return view('procurement::supplier-invoices.create', [
            'brands' => Brand::query()->orderBy('name')->get(),
            'invoice' => new SupplierInvoice([
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'currency_code' => $po?->currency_code ?? Organization::current()->currency_code,
                'purchase_order_id' => $po?->id,
                'vendor_id' => $po?->vendor_id,
                'status' => SupplierInvoiceStatus::Draft,
            ]),
            'purchaseOrders' => PurchaseOrder::query()
                ->whereIn('status', ['issued', 'partially_received', 'received'])
                ->with('vendor:id,code,name')
                ->orderByDesc('order_date')
                ->get(['id', 'number', 'vendor_id', 'currency_code']),
            'purchaseOrder' => $po,
            'prefilledLines' => $prefilledLines,
        ]);
    }

    public function store(StoreSupplierInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoices->create(
            SupplierInvoiceData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('procurement.supplier-invoices.show', $invoice)
            ->with('status', "Invoice {$invoice->number} created as draft.");
    }

    public function show(SupplierInvoice $supplierInvoice): View
    {
        $this->authorize('view', $supplierInvoice);

        return view('procurement::supplier-invoices.show', [
            'invoice' => $supplierInvoice->load([
                'lines.brand', 'lines.item', 'lines.purchaseOrderLine',
                'lines.debitAccount', 'lines.inputTaxAccount',
                'vendor', 'purchaseOrder', 'journalEntry',
                'paymentAllocations.payment', 'paymentAllocations.allocator',
                'creator', 'matcher', 'approver', 'poster',
            ]),
        ]);
    }

    public function edit(SupplierInvoice $supplierInvoice): View
    {
        $this->authorize('update', $supplierInvoice);

        if ($supplierInvoice->purchase_order_id === null) {
            return view('procurement::supplier-invoices.standalone', [
                'brands' => Brand::query()->orderBy('name')->get(),
                'invoice' => $supplierInvoice->load('lines'),
                'vendors' => Vendor::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
                'expenseAccounts' => Account::query()->active()->postable()->ofType(AccountType::Expense)->orderBy('code')->get(['id', 'code', 'name']),
            ]);
        }

        return view('procurement::supplier-invoices.edit', [
            'brands' => Brand::query()->orderBy('name')->get(),
            'invoice' => $supplierInvoice->load('lines'),
            'purchaseOrder' => $supplierInvoice->purchaseOrder->load(['lines.brand', 'lines.item']),
            'purchaseOrders' => collect(),
        ]);
    }

    public function update(UpdateSupplierInvoiceRequest $request, SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $this->invoices->update(
            $supplierInvoice,
            SupplierInvoiceData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('procurement.supplier-invoices.show', $supplierInvoice)
            ->with('status', 'Invoice updated. Please re-run the three-way match.');
    }

    public function destroy(SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $this->authorize('delete', $supplierInvoice);
        $this->invoices->delete($supplierInvoice);

        return redirect()->route('procurement.supplier-invoices.index')
            ->with('status', 'Invoice deleted.');
    }

    public function match(SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $this->authorize('match', $supplierInvoice);

        $invoice = $this->invoices->match($supplierInvoice, auth()->id());

        $status = $invoice->match_status === 'matched' ? 'success' : 'warning';

        return back()->with('status', "Match result: {$invoice->match_status}.")
            ->with('status_level', $status);
    }

    public function approve(ApproveSupplierInvoiceRequest $request, SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $override = $request->boolean('override_mismatch');

        if ($override) {
            $this->authorize('overrideMismatch', $supplierInvoice);
        }

        $this->invoices->approve($supplierInvoice, auth()->id(), $override);

        return back()->with('status', 'Invoice approved.');
    }

    public function reject(Request $request, SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $this->authorize('reject', $supplierInvoice);

        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);

        $this->invoices->reject(
            $supplierInvoice,
            auth()->id(),
            $request->string('reason')->toString(),
        );

        return back()->with('status', 'Invoice rejected.');
    }

    public function post(SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $this->authorize('post', $supplierInvoice);

        $invoice = $this->invoices->post($supplierInvoice, auth()->id());

        return back()->with(
            'status',
            "Invoice {$invoice->number} posted to GL as entry ".
            ($invoice->journalEntry?->number ?? '—').'.',
        );
    }

    public function cancel(Request $request, SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $this->authorize('cancel', $supplierInvoice);

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->invoices->cancel($supplierInvoice, auth()->id(), $request->input('reason'));

        return back()->with('status', 'Invoice cancelled.');
    }
}
