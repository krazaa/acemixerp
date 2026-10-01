<?php

namespace Modules\Procurement\Http\Controllers;

use App\Contracts\CostCenterManager;
use App\Contracts\DepartmentManager;
use App\Contracts\ItemManager;
use App\Contracts\UnitManager;
use App\Contracts\WarehouseManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\Origin;
use Modules\Procurement\Contracts\PurchaseRequisitionManager;
use Modules\Procurement\Data\PurchaseRequisitionData;
use Modules\Procurement\Enums\PurchaseRequisitionStatus;
use Modules\Procurement\Http\Requests\PurchaseRequisitions\RejectPurchaseRequisitionRequest;
use Modules\Procurement\Http\Requests\PurchaseRequisitions\StorePurchaseRequisitionRequest;
use Modules\Procurement\Http\Requests\PurchaseRequisitions\UpdatePurchaseRequisitionRequest;
use Modules\Procurement\Models\PurchaseRequisition;

class PurchaseRequisitionController extends Controller
{
    public function __construct(
        private readonly PurchaseRequisitionManager $requisitions,
        private readonly ItemManager $items,
        private readonly UnitManager $units,
        private readonly DepartmentManager $departments,
        private readonly CostCenterManager $costCenters,
        private readonly WarehouseManager $warehouses,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseRequisition::class);

        return view('procurement::purchase-requisitions.index', [
            'requisitions' => $this->requisitions->paginate(
                $request->only(['search', 'status', 'department_id', 'from', 'to'])
            ),
            'departments' => $this->departments->allActive(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseRequisition::class);

        return view('procurement::purchase-requisitions.create', $this->formDependencies() + [
            'requisition' => new PurchaseRequisition([
                'requested_date' => now()->toDateString(),
                'status' => PurchaseRequisitionStatus::Draft,
            ]),
        ]);
    }

    public function store(StorePurchaseRequisitionRequest $request): RedirectResponse
    {
        $pr = $this->requisitions->create(
            PurchaseRequisitionData::fromRequest($request, $request->user()->id),
            $request->user()->id,
        );

        return redirect()->route('procurement.purchase-requisitions.show', $pr)
            ->with('status', "Purchase requisition {$pr->number} created as draft.");
    }

    public function show(PurchaseRequisition $purchaseRequisition): View
    {
        $this->authorize('view', $purchaseRequisition);

        return view('procurement::purchase-requisitions.show', [
            'requisition' => $purchaseRequisition->load([
                'lines.brand', 'lines.origin', 'lines.item', 'lines.unit',
                'department', 'costCenter', 'requester', 'warehouse',
                'submitter', 'approver', 'rejecter',
                'creator', 'updater', 'rfqs',
            ]),
        ]);
    }

    public function edit(PurchaseRequisition $purchaseRequisition): View
    {
        $this->authorize('update', $purchaseRequisition);

        return view('procurement::purchase-requisitions.edit', $this->formDependencies() + [
            'requisition' => $purchaseRequisition->load('lines'),
        ]);
    }

    public function update(UpdatePurchaseRequisitionRequest $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->requisitions->update(
            $purchaseRequisition,
            PurchaseRequisitionData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()->route('procurement.purchase-requisitions.show', $purchaseRequisition)
            ->with('status', 'Purchase requisition updated.');
    }

    public function destroy(PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->authorize('delete', $purchaseRequisition);
        $this->requisitions->delete($purchaseRequisition);

        return redirect()->route('procurement.purchase-requisitions.index')
            ->with('status', 'Purchase requisition deleted.');
    }

    public function submit(PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->authorize('submit', $purchaseRequisition);
        $this->requisitions->submit($purchaseRequisition, auth()->id());

        return back()->with('status', 'Purchase requisition submitted for approval.');
    }

    public function startReview(PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->authorize('review', $purchaseRequisition);
        $this->requisitions->startReview($purchaseRequisition, auth()->id());

        return back()->with('status', 'Purchase requisition under review.');
    }

    public function approve(PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->authorize('approve', $purchaseRequisition);
        $this->requisitions->approve($purchaseRequisition, auth()->id());

        return back()->with('status', 'Purchase requisition approved.');
    }

    public function reject(RejectPurchaseRequisitionRequest $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->requisitions->reject(
            $purchaseRequisition,
            auth()->id(),
            $request->string('reason')->toString(),
        );

        return back()->with('status', 'Purchase requisition rejected.');
    }

    public function cancel(Request $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->authorize('cancel', $purchaseRequisition);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->requisitions->cancel($purchaseRequisition, auth()->id(), $request->input('reason'));

        return back()->with('status', 'Purchase requisition cancelled.');
    }

    public function close(PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->authorize('close', $purchaseRequisition);
        $this->requisitions->close($purchaseRequisition, auth()->id());

        return back()->with('status', 'Purchase requisition closed.');
    }

    /** @return array<string, mixed> */
    private function formDependencies(): array
    {
        return [
            'brands' => Brand::query()->orderBy('name')->get(),
            'origins' => Origin::query()->orderBy('name')->get(),
            'items' => $this->items->allPurchasable(),
            'units' => $this->units->allActive(),
            'departments' => $this->departments->allActive(),
            'costCenters' => $this->costCenters->allActive(),
            'warehouses' => $this->warehouses->allActive(),
        ];
    }
}
