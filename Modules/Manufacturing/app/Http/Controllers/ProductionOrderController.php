<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Http\Controllers;

use App\Contracts\WarehouseManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Manufacturing\Contracts\BomManager;
use Modules\Manufacturing\Contracts\ProductionOrderManager;
use Modules\Manufacturing\Data\ProductionOrderData;
use Modules\Manufacturing\Enums\ProductionOrderStatus;
use Modules\Manufacturing\Http\Requests\ProductionOrders\CompleteProductionOrderRequest;
use Modules\Manufacturing\Http\Requests\ProductionOrders\StoreProductionOrderRequest;
use Modules\Manufacturing\Http\Requests\ProductionOrders\UpdateProductionOrderRequest;
use Modules\Manufacturing\Models\BillOfMaterials;
use Modules\Manufacturing\Models\ProductionOrder;

class ProductionOrderController extends Controller
{
    public function __construct(
        private readonly ProductionOrderManager $orders,
        private readonly BomManager $boms,
        private readonly WarehouseManager $warehouses,
    ) {}

    /**
     * Paginated list of production orders with filters.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ProductionOrder::class);

        return view('manufacturing::production-orders.index', [
            'orders' => $this->orders->paginate(
                $request->only(['search', 'status', 'type', 'from', 'to'])
            ),
        ]);
    }

    /**
     * Create form. If ?bom_id= is present, pre-select that BOM.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', ProductionOrder::class);

        $bomId = $request->integer('bom_id');
        $bom = $bomId
            ? BillOfMaterials::query()->with('product:id,code,name')->findOrFail($bomId)
            : null;

        return view('manufacturing::production-orders.create', [
            'order' => new ProductionOrder([
                'planned_quantity' => 1,
                'type' => 'standard',
                'status' => ProductionOrderStatus::Draft,
            ]),
            'bom' => $bom,
            'activeBoms' => $this->boms->allActive(),
            'warehouses' => $this->warehouses->allActive(),
        ]);
    }

    public function store(StoreProductionOrderRequest $request): RedirectResponse
    {
        $order = $this->orders->create(
            ProductionOrderData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('manufacturing.production-orders.show', $order)
            ->with('status', "Production order {$order->number} created as draft.");
    }

    public function show(ProductionOrder $productionOrder): View
    {
        $this->authorize('view', $productionOrder);

        return view('manufacturing::production-orders.show', [
            'order' => $productionOrder->load([
                'lines.component',
                'lines.unit',
                'outputs.product',
                'outputs.producer',
                'outputs.journalEntry',
                'bom',
                'product',
                'salesOrder',
                'sourceWarehouse',
                'planner',
                'releaser',
                'starter',
                'completer',
                'closer',
                'creator',
                'updater',
                'journalEntry',
            ]),
        ]);
    }

    public function print(ProductionOrder $productionOrder): View
    {
        $this->authorize('view', $productionOrder);

        return view('manufacturing::production-orders.print', [
            'order' => $productionOrder->load([
                'lines.component', 'lines.batch', 'lines.unit', 'product', 'bom', 'sourceWarehouse',
            ]),
        ]);
    }

    public function edit(ProductionOrder $productionOrder): View
    {
        $this->authorize('update', $productionOrder);

        return view('manufacturing::production-orders.edit', [
            'order' => $productionOrder->load('lines'),
            'bom' => $productionOrder->bom,
            'activeBoms' => $this->boms->allActive(),
            'warehouses' => $this->warehouses->allActive(),
        ]);
    }

    public function update(UpdateProductionOrderRequest $request, ProductionOrder $productionOrder): RedirectResponse
    {
        $this->orders->update(
            $productionOrder,
            ProductionOrderData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('manufacturing.production-orders.show', $productionOrder)
            ->with('status', 'Production order updated.');
    }

    public function destroy(ProductionOrder $productionOrder): RedirectResponse
    {
        $this->authorize('cancel', $productionOrder);

        if (! $productionOrder->status->canCancel()) {
            return back()->withErrors([
                'error' => 'Cannot delete a production order in status '
                         .$productionOrder->status->label().'.',
            ]);
        }

        $productionOrder->delete();

        return redirect()
            ->route('manufacturing.production-orders.index')
            ->with('status', 'Production order deleted.');
    }

    // ─── Workflow actions ────────────────────────────────────────────

    public function plan(ProductionOrder $productionOrder): RedirectResponse
    {
        $this->authorize('plan', $productionOrder);

        $this->orders->plan($productionOrder, auth()->id());

        return back()->with('status', 'Production order planned.');
    }

    public function release(ProductionOrder $productionOrder): RedirectResponse
    {
        $this->authorize('release', $productionOrder);

        $this->orders->release($productionOrder, auth()->id());

        return back()->with('status', 'Production order released. Component stock verified.');
    }

    public function start(ProductionOrder $productionOrder): RedirectResponse
    {
        $this->authorize('start', $productionOrder);

        $this->orders->start($productionOrder, auth()->id());

        return back()->with('status', 'Production started. Components issued from source warehouse.');
    }

    public function complete(CompleteProductionOrderRequest $request, ProductionOrder $productionOrder): RedirectResponse
    {
        $this->authorize('complete', $productionOrder);

        $this->orders->complete(
            $productionOrder,
            $request->string('produced_quantity')->toString(),
            auth()->id(),
        );

        return back()->with('status', 'Production output recorded. Finished goods added to stock.');
    }

    public function close(ProductionOrder $productionOrder): RedirectResponse
    {
        $this->authorize('close', $productionOrder);

        $this->orders->close($productionOrder, auth()->id());

        return back()->with('status', 'Production order closed.');
    }

    public function cancel(Request $request, ProductionOrder $productionOrder): RedirectResponse
    {
        $this->authorize('cancel', $productionOrder);

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->orders->cancel(
            $productionOrder,
            auth()->id(),
            $request->input('reason'),
        );

        return back()->with('status', 'Production order cancelled.');
    }
}
