<?php

namespace Modules\Procurement\Http\Controllers;

use App\Contracts\CostCenterManager;
use App\Contracts\DepartmentManager;
use App\Contracts\ItemManager;
use App\Contracts\UnitManager;
use App\Contracts\VendorManager;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Models\Brand;
use Modules\Procurement\Contracts\PurchaseOrderManager;
use Modules\Procurement\Contracts\RfqManager;
use Modules\Procurement\Contracts\VendorQuotationManager;
use Modules\Procurement\Data\RfqData;
use Modules\Procurement\Enums\RfqStatus;
use Modules\Procurement\Http\Requests\Rfqs\StoreRfqRequest;
use Modules\Procurement\Http\Requests\Rfqs\UpdateRfqRequest;
use Modules\Procurement\Models\RequestForQuotation;

class RfqController extends Controller
{
    public function __construct(
        private readonly RfqManager $rfqs,
        private readonly VendorQuotationManager $quotations,
        private readonly ItemManager $items,
        private readonly UnitManager $units,
        private readonly VendorManager $vendors,
        private readonly DepartmentManager $departments,
        private readonly CostCenterManager $costCenters,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', RequestForQuotation::class);

        return view('procurement::rfqs.index', [
            'rfqs' => $this->rfqs->paginate($request->only(['search', 'status', 'department_id', 'from', 'to'])),
            'departments' => $this->departments->allActive(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', RequestForQuotation::class);

        return view('procurement::rfqs.create', $this->dependencies() + [
            'rfq' => new RequestForQuotation([
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'currency_code' => Organization::current()->currency_code,
                'status' => RfqStatus::Draft,
            ]),
        ]);
    }

    public function store(StoreRfqRequest $request): RedirectResponse
    {
        $rfq = $this->rfqs->create(RfqData::fromRequest($request), $request->user()->id);

        return redirect()->route('procurement.rfqs.show', $rfq)
            ->with('status', "RFQ {$rfq->number} created as draft.");
    }

    public function show(RequestForQuotation $rfq): View
    {
        $this->authorize('view', $rfq);

        return view('procurement::rfqs.show', [
            'rfq' => $rfq->load([
                'lines.brand', 'lines.item', 'lines.unit',
                'vendors.vendor', 'quotations.vendor', 'quotations.lines',
                'department', 'costCenter', 'issuer', 'awarder', 'awardedQuotation.vendor',
                'lines.awardedQuotationLine.quotation.vendor', 'purchaseOrders.vendor',
            ]),
        ]);
    }

    public function printRequest(RequestForQuotation $rfq, ?Vendor $vendor = null): View
    {
        $this->authorize('view', $rfq);

        abort_unless($rfq->status->canReceive(), 404);

        if ($vendor !== null) {
            abort_unless($rfq->vendors()->where('vendor_id', $vendor->id)->exists(), 404);
            $vendor->load('addresses');
        }

        return view('procurement::rfqs.print-request', [
            'rfq' => $rfq->load(['lines.item', 'lines.brand', 'lines.origin', 'lines.unit']),
            'organization' => Organization::currentOrNull(),
            'vendor' => $vendor,
        ]);
    }

    public function print(RequestForQuotation $rfq): View
    {
        $this->authorize('view', $rfq);

        abort_unless(
            $rfq->status === RfqStatus::Awarded,
            404,
        );

        return view('procurement::rfqs.print', [
            'rfq' => $rfq->load([
                'lines.brand', 'lines.item',
                'lines.unit',
                'department',
                'costCenter',
                'issuer',
                'awarder',
                'awardedQuotation.vendor',
                'awardedQuotation.lines.item',
                'awardedQuotation.lines.rfqLine',
                'lines.awardedQuotationLine.quotation.vendor',
            ]),
        ]);
    }

    public function edit(RequestForQuotation $rfq): View
    {
        $this->authorize('update', $rfq);

        return view('procurement::rfqs.edit', $this->dependencies() + [
            'rfq' => $rfq->load(['lines', 'vendors']),
        ]);
    }

    public function update(UpdateRfqRequest $request, RequestForQuotation $rfq): RedirectResponse
    {
        $this->rfqs->update($rfq, RfqData::fromRequest($request), $request->user()->id);

        return redirect()->route('procurement.rfqs.show', $rfq)
            ->with('status', 'RFQ updated.');
    }

    public function destroy(RequestForQuotation $rfq): RedirectResponse
    {
        $this->authorize('delete', $rfq);
        $this->rfqs->delete($rfq);

        return redirect()->route('procurement.rfqs.index')->with('status', 'RFQ deleted.');
    }

    public function issue(RequestForQuotation $rfq): RedirectResponse
    {
        $this->authorize('issue', $rfq);
        $this->rfqs->issue($rfq, auth()->id());

        return back()->with('status', 'RFQ issued to vendors.');
    }

    public function cancel(Request $request, RequestForQuotation $rfq): RedirectResponse
    {
        $this->authorize('cancel', $rfq);
        $this->rfqs->cancel($rfq, auth()->id(), $request->input('reason'));

        return back()->with('status', 'RFQ cancelled.');
    }

    public function close(RequestForQuotation $rfq): RedirectResponse
    {
        $this->authorize('close', $rfq);
        $this->rfqs->close($rfq, auth()->id());

        return back()->with('status', 'RFQ closed.');
    }

    public function compare(RequestForQuotation $rfq): View
    {
        $this->authorize('view', $rfq);

        return view('procurement::rfqs.compare', [
            'rfq' => $rfq,
            'comparison' => $this->quotations->comparison($rfq),
        ]);
    }

    public function createPo(RequestForQuotation $rfq, PurchaseOrderManager $orders): RedirectResponse
    {
        $this->authorize('createPurchaseOrders', $rfq);
        $created = $orders->createOrdersFromRfq($rfq, auth()->id());

        return redirect()->route('procurement.rfqs.compare', $rfq)
            ->with('status', $created->count().' draft purchase order(s) created: '.$created->pluck('number')->join(', '));
    }

    private function dependencies(): array
    {
        return [
            'brands' => Brand::query()->orderBy('name')->get(),
            'items' => $this->items->allPurchasable(),
            'units' => $this->units->allActive(),
            'vendors' => $this->vendors->allSupplier(),
            'departments' => $this->departments->allActive(),
            'costCenters' => $this->costCenters->allActive(),
        ];
    }
}
