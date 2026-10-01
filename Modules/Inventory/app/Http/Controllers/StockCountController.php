<?php

namespace Modules\Inventory\Http\Controllers;

use App\Contracts\WarehouseManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Contracts\StockCountManager;
use Modules\Inventory\Data\StockCountData;
use Modules\Inventory\Enums\StockCountStatus;
use Modules\Inventory\Http\Requests\Counts\RecordStockCountRequest;
use Modules\Inventory\Http\Requests\Counts\StoreStockCountRequest;
use Modules\Inventory\Models\StockCount;

class StockCountController extends Controller
{
    public function __construct(
        private readonly StockCountManager $counts,
        private readonly WarehouseManager $warehouses,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockCount::class);

        return view('inventory::counts.index', [
            'counts' => $this->counts->paginate(
                $request->only(['search', 'status', 'warehouse_id'])
            ),
            'warehouses' => $this->warehouses->allActive(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', StockCount::class);

        return view('inventory::counts.create', [
            'warehouses' => $this->warehouses->allActive(),
            'count' => new StockCount([
                'count_date' => now()->toDateString(),
                'status' => StockCountStatus::Draft,
            ]),
        ]);
    }

    public function store(StoreStockCountRequest $request): RedirectResponse
    {
        $count = $this->counts->create(
            StockCountData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('inventory.counts.show', $count)
            ->with('status', "Stock count {$count->number} created. Start counting to snapshot the system quantities.");
    }

    public function show(StockCount $count): View
    {
        $this->authorize('view', $count);

        return view('inventory::counts.show', [
            'count' => $count->load([
                'lines.item.unit',
                'warehouse',
                'starter', 'submitter', 'approver', 'poster',
                'stockAdjustment', 'creator', 'updater',
            ]),
        ]);
    }

    public function edit(StockCount $count): View
    {
        $this->authorize('update', $count);

        // Editing a count that has already been started is not allowed.
        return view('inventory::counts.edit', [
            'count' => $count,
        ]);
    }

    public function update(Request $request, StockCount $count): RedirectResponse
    {
        $this->authorize('update', $count);

        $request->validate([
            'scope' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $count->status->canStart()) {
            return back()->withErrors(['error' => 'Only draft counts can be edited.']);
        }

        $count->update([
            'scope' => $request->input('scope'),
            'notes' => $request->input('notes'),
        ]);

        return back()->with('status', 'Count updated.');
    }

    public function destroy(StockCount $count): RedirectResponse
    {
        $this->authorize('delete', $count);

        if ($count->status->value !== 'draft') {
            return back()->withErrors(['error' => 'Only drafts can be deleted. Cancel instead.']);
        }

        $count->delete();

        return redirect()->route('inventory.counts.index')
            ->with('status', 'Count deleted.');
    }

    public function start(StockCount $count): RedirectResponse
    {
        $this->authorize('start', $count);
        $this->counts->start($count, auth()->id());

        return back()->with('status', 'Stock count started. Enter counted quantities.');
    }

    public function snapshot(StockCount $count): RedirectResponse
    {
        $this->authorize('update', $count);
        $this->counts->snapshot($count);

        return back()->with('status', 'Current warehouse stock loaded. Enter the physical quantities you counted.');
    }

    public function record(RecordStockCountRequest $request, StockCount $count): RedirectResponse
    {
        $this->authorize('update', $count);

        $counted = collect($request->input('counted', []))
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (string) $v])
            ->all();

        $this->counts->recordCount($count, $counted, auth()->id());

        return back()->with('status', 'Counted quantities saved.');
    }

    public function submit(StockCount $count): RedirectResponse
    {
        $this->authorize('submit', $count);
        $this->counts->submit($count, auth()->id());

        return back()->with('status', 'Stock count submitted for review.');
    }

    public function approve(StockCount $count): RedirectResponse
    {
        $this->authorize('approve', $count);
        $this->counts->approve($count, auth()->id());

        return back()->with('status', 'Stock count approved.');
    }

    public function post(StockCount $count): RedirectResponse
    {
        $this->authorize('post', $count);
        $count = $this->counts->post($count, auth()->id());

        $msg = $count->stock_adjustment_id
            ? "Stock count posted. Adjustment {$count->stockAdjustment?->number} created for variances."
            : 'Stock count posted with zero variances. No adjustment needed.';

        return back()->with('status', $msg);
    }

    public function cancel(Request $request, StockCount $count): RedirectResponse
    {
        $this->authorize('cancel', $count);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->counts->cancel($count, auth()->id(), $request->input('reason'));

        return back()->with('status', 'Stock count cancelled.');
    }
}
