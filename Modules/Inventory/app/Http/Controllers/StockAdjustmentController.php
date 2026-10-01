<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Contracts\ItemManager;
use App\Contracts\WarehouseManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Contracts\StockAdjustmentManager;
use Modules\Inventory\Data\StockAdjustmentData;
use Modules\Inventory\Enums\AdjustmentStatus;
use Modules\Inventory\Http\Requests\Adjustments\StoreStockAdjustmentRequest;
use Modules\Inventory\Http\Requests\Adjustments\UpdateStockAdjustmentRequest;
use Modules\Inventory\Models\StockAdjustment;

class StockAdjustmentController extends Controller
{
    public function __construct(
        private readonly StockAdjustmentManager $adjustments,
        private readonly WarehouseManager $warehouses,
        private readonly ItemManager $items,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockAdjustment::class);

        return view('inventory::adjustments.index', [
            'adjustments' => $request->boolean('print')
                ? $this->adjustments->all($request->only(['search', 'status', 'warehouse_id']))
                : $this->adjustments->paginate($request->only(['search', 'status', 'warehouse_id'])),
            'warehouses' => $this->warehouses->allActive(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', StockAdjustment::class);

        return view('inventory::adjustments.create', $this->formDependencies() + [
            'adjustment' => new StockAdjustment([
                'adjustment_date' => now()->toDateString(),
                'status' => AdjustmentStatus::Draft,
            ]),
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request): RedirectResponse
    {
        $adjustment = $this->adjustments->create(
            StockAdjustmentData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('inventory.adjustments.show', $adjustment)
            ->with('status', "Adjustment {$adjustment->number} created as draft.");
    }

    public function show(StockAdjustment $adjustment): View
    {
        $this->authorize('view', $adjustment);

        return view('inventory::adjustments.show', [
            'adjustment' => $adjustment->load([
                'lines.item', 'lines.batch',
                'warehouse',
                'submitter', 'approver', 'poster', 'creator', 'updater',
                'journalEntry',
            ]),
        ]);
    }

    public function edit(StockAdjustment $adjustment): View
    {
        $this->authorize('update', $adjustment);

        return view('inventory::adjustments.edit', $this->formDependencies() + [
            'adjustment' => $adjustment->load('lines'),
        ]);
    }

    public function update(UpdateStockAdjustmentRequest $request, StockAdjustment $adjustment): RedirectResponse
    {
        $this->adjustments->update($adjustment, StockAdjustmentData::fromRequest($request), $request->user()->id);

        return redirect()
            ->route('inventory.adjustments.show', $adjustment)
            ->with('status', 'Adjustment updated.');
    }

    public function destroy(StockAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('delete', $adjustment);

        if ($adjustment->status->value !== 'draft') {
            return back()->withErrors(['error' => 'Only drafts can be deleted. Cancel instead.']);
        }

        $adjustment->delete();

        return redirect()->route('inventory.adjustments.index')
            ->with('status', 'Adjustment deleted.');
    }

    public function submit(StockAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('submit', $adjustment);
        $this->adjustments->submit($adjustment, auth()->id());

        return back()->with('status', 'Adjustment submitted.');
    }

    public function approve(StockAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('approve', $adjustment);
        $this->adjustments->approve($adjustment, auth()->id());

        return back()->with('status', 'Adjustment approved.');
    }

    public function post(StockAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('post', $adjustment);
        $this->adjustments->post($adjustment, auth()->id());

        return back()->with('status', 'Adjustment posted. Stock movements recorded.');
    }

    public function cancel(Request $request, StockAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('cancel', $adjustment);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->adjustments->cancel($adjustment, auth()->id(), $request->input('reason'));

        return back()->with('status', 'Adjustment cancelled.');
    }

    /** @return array<string, mixed> */
    private function formDependencies(): array
    {
        return [
            'warehouses' => $this->warehouses->allActive(),
            'items' => $this->items->allPurchasablestock(),
        ];
    }
}
