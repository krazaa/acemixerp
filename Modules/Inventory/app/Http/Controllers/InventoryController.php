<?php

namespace Modules\Inventory\Http\Controllers;

use App\Contracts\StockLedger;
use App\Contracts\WarehouseManager;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Organization;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockMovement;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly WarehouseManager $warehouses,
    ) {}

    /**
     * Inventory dashboard: KPIs, low stock, recent movements, valuation.
     */
    public function dashboard(): View
    {
        $this->authorize('inventory.view');

        $kpis = [
            'total_items' => Item::query()->where('item_type', 'stock')->where('status', 'active')->count(),
            'total_warehouses' => Warehouse::query()->where('status', 'active')->count(),
            'total_stock_value' => (string) StockBalance::query()->sum('total_value'),
            'negative_balances' => StockBalance::query()->where('quantity', '<', 0)->count(),
            'low_stock_items' => $this->lowStockCount(),
            'movements_last_24h' => StockMovement::query()
                ->where('occurred_at', '>=', now()->subDay())
                ->count(),
        ];

        $lowStock = $this->lowStockQuery()
            ->with(['item:id,code,name,reorder_level,minimum_stock', 'warehouse:id,code,name'])
            ->orderBy('quantity')
            ->limit(10)
            ->get();

        $recentMovements = StockMovement::query()
            ->with(['item:id,code,name', 'warehouse:id,code,name', 'creator:id,name'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $warehouseSummary = StockBalance::query()
            ->selectRaw('warehouse_id, COUNT(DISTINCT item_id) AS item_count, SUM(quantity) AS total_qty, SUM(total_value) AS total_value')
            ->groupBy('warehouse_id')
            ->with('warehouse:id,code,name')
            ->get();

        return view('inventory::inventory.dashboard', [
            'kpis' => $kpis,
            'lowStock' => $lowStock,
            'recentMovements' => $recentMovements,
            'warehouseSummary' => $warehouseSummary,
        ]);
    }

    /**
     * Warehouse overview: stock within one warehouse with quick drill-down.
     */
    public function warehouse(Request $request, Warehouse $warehouse): View
    {
        $this->authorize('inventory.view');

        $balances = StockBalance::query()
            ->with([
                'item:id,code,name,item_type,reorder_level,unit_id',
                'item.unit:id,code,name',
                'batch:id,number,expiry_date',
            ])
            ->where('warehouse_id', $warehouse->id)
            ->when($request->string('search')->toString(), function ($q, $s) {
                $q->whereHas('item', fn ($q) => $q
                    ->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%"));
            })
            ->orderBy('item_id')
            ->paginate(50)
            ->withQueryString();

        $totalValue = StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->sum('total_value');

        return view('inventory::inventory.warehouse', [
            'warehouse' => $warehouse,
            'balances' => $balances,
            'totalValue' => (string) $totalValue,
        ]);
    }

    /**
     * Low stock alert list: items at or below reorder level, with supplier info.
     */
    public function lowStock(Request $request): View
    {
        $this->authorize('inventory.view');

        $query = $this->lowStockQuery()
            ->with([
                'item:id,code,name,reorder_level,minimum_stock,maximum_stock,unit_id',
                'item.unit:id,code,name',
                'warehouse:id,code,name',
            ])
            ->orderBy('item_id')
            ->orderBy('warehouse_id');

        $rows = $request->boolean('print')
            ? $query->get()
            : $query->paginate(50);

        return view('inventory::inventory.low-stock', ['rows' => $rows]);
    }

    /**
     * Inventory position by warehouse.
     */
    public function summary(Request $request): View
    {
        $this->authorize('reports.view');

        $summaryQuery = $this->inventorySummaryRowsQuery($request);
        $totals = DB::query()
            ->fromSub((clone $summaryQuery)->toBase(), 'inventory_summary')
            ->selectRaw('COALESCE(SUM(item_count), 0) as item_count')
            ->selectRaw('COALESCE(SUM(total_quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(total_reserved), 0) as total_reserved')
            ->selectRaw('COALESCE(SUM(total_available), 0) as total_available')
            ->selectRaw('COALESCE(SUM(total_value), 0) as total_value')
            ->first();
        $rows = $summaryQuery->paginate(50)->withQueryString();

        return view('inventory::inventory.summary', [
            'rows' => $rows,
            'totals' => $totals,
            'warehouses' => $this->warehouses->allActive(),
        ]);
    }

    /**
     * Printer-friendly inventory summary for the current filter set.
     */
    public function summaryPrint(Request $request): View
    {
        $this->authorize('reports.view');

        return view('inventory::inventory.summary-print', [
            'rows' => $this->inventorySummaryRowsQuery($request)->get(),
            'warehouse' => $request->integer('warehouse_id')
                ? Warehouse::query()->find($request->integer('warehouse_id'))
                : null,
        ]);
    }

    /**
     * Download the inventory summary as CSV for spreadsheet analysis.
     */
    public function summaryCsv(Request $request): StreamedResponse
    {
        $this->authorize('reports.view');

        $rows = $this->inventorySummaryRowsQuery($request)->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Warehouse Code', 'Warehouse', 'Distinct Items', 'On Hand', 'Reserved', 'Available', 'Inventory Value']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->warehouse?->code,
                    $row->warehouse?->name,
                    $row->item_count,
                    $row->total_quantity,
                    $row->total_reserved,
                    $row->total_available,
                    $row->total_value,
                ]);
            }
        }, 'inventory-summary-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Stock valuation report — total value per item.
     */
    public function valuation(Request $request): View
    {
        $this->authorize('reports.view');

        $method = Organization::current()->inventory_valuation_method;

        $valuationQuery = $this->valuationRowsQuery($request);
        $summary = DB::query()
            ->fromSub((clone $valuationQuery)->toBase(), 'inventory_valuation')
            ->selectRaw('COALESCE(SUM(total_quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(total_reserved), 0) as total_reserved')
            ->selectRaw('COALESCE(SUM(total_available), 0) as total_available')
            ->selectRaw('COALESCE(SUM(total_value), 0) as total_value')
            ->first();
        $rows = $valuationQuery->paginate(50)->withQueryString();

        return view('inventory::inventory.valuation', [
            'rows' => $rows,
            'summary' => $summary,
            'method' => $method,
            'warehouses' => $this->warehouses->allActive(),
        ]);
    }

    /**
     * Printer-friendly stock valuation report for the current filter set.
     */
    public function valuationPrint(Request $request): View
    {
        $this->authorize('reports.view');

        $rows = $this->valuationRowsQuery($request)->get();

        return view('inventory::inventory.valuation-print', [
            'rows' => $rows,
            'method' => Organization::current()->inventory_valuation_method,
            'warehouse' => $request->integer('warehouse_id')
                ? Warehouse::query()->find($request->integer('warehouse_id'))
                : null,
        ]);
    }

    /**
     * Download the stock valuation report as CSV for spreadsheet analysis.
     */
    public function valuationCsv(Request $request): StreamedResponse
    {
        $this->authorize('reports.view');

        $rows = $this->valuationRowsQuery($request)->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Item Code', 'Item', 'Unit', 'On Hand', 'Reserved', 'Available', 'Average Unit Cost', 'Total Value']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->item?->code,
                    $row->item?->name,
                    $row->item?->unit?->name,
                    $row->total_quantity,
                    $row->total_reserved,
                    $row->total_available,
                    $row->avg_unit_cost,
                    $row->total_value,
                ]);
            }
        }, 'inventory-valuation-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Simple JSON endpoint for dashboards/autocomplete: on-hand quantity of
     * an item in a warehouse.
     */
    public function onHand(Request $request): array
    {
        $this->authorize('inventory.view');

        $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
        ]);

        return [
            'item_id' => $request->integer('item_id'),
            'warehouse_id' => $request->integer('warehouse_id'),
            'on_hand' => $this->ledger->onHand(
                $request->integer('item_id'),
                $request->integer('warehouse_id'),
            ),
            'available' => $this->ledger->available(
                $request->integer('item_id'),
                $request->integer('warehouse_id'),
            ),
        ];
    }

    // ─── Internals ───────────────────────────────────────────────────

    /**
     * @return Builder<StockBalance>
     */
    private function lowStockQuery(): Builder
    {
        $warehouseBalances = StockBalance::query()
            ->selectRaw('item_id, warehouse_id, SUM(quantity) AS quantity')
            ->groupBy('item_id', 'warehouse_id');

        return StockBalance::query()
            ->fromSub($warehouseBalances, 'stock_balances')
            ->whereHas('item', fn ($q) => $q
                ->where('reorder_level', '>', 0)
                ->whereColumn('stock_balances.quantity', '<=', 'items.reorder_level'));
    }

    private function lowStockCount(): int
    {
        return $this->lowStockQuery()->count();
    }

    /**
     * @return Builder<StockBalance>
     */
    private function valuationRowsQuery(Request $request): Builder
    {
        return StockBalance::query()
            ->selectRaw('item_id')
            ->selectRaw('SUM(quantity) AS total_quantity')
            ->selectRaw('SUM(reserved_quantity) AS total_reserved')
            ->selectRaw('SUM(quantity - reserved_quantity) AS total_available')
            ->selectRaw('SUM(total_value) AS total_value')
            ->selectRaw('CASE WHEN SUM(quantity) > 0 THEN SUM(total_value) / SUM(quantity) ELSE 0 END AS avg_unit_cost')
            ->when($request->integer('warehouse_id'), fn (Builder $query, int $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($request->string('search')->toString(), function (Builder $query, string $search): void {
                $query->whereHas('item', fn (Builder $itemQuery) => $itemQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            })
            ->groupBy('item_id')
            ->with(['item:id,code,name,unit_id', 'item.unit:id,code,name'])
            ->orderByDesc('total_value')
            ->orderBy('item_id');
    }

    /**
     * @return Builder<StockBalance>
     */
    private function inventorySummaryRowsQuery(Request $request): Builder
    {
        return StockBalance::query()
            ->selectRaw('warehouse_id')
            ->selectRaw('COUNT(DISTINCT item_id) AS item_count')
            ->selectRaw('SUM(quantity) AS total_quantity')
            ->selectRaw('SUM(reserved_quantity) AS total_reserved')
            ->selectRaw('SUM(quantity - reserved_quantity) AS total_available')
            ->selectRaw('SUM(total_value) AS total_value')
            ->when($request->integer('warehouse_id'), fn (Builder $query, int $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->groupBy('warehouse_id')
            ->with('warehouse:id,code,name')
            ->orderByDesc('total_value')
            ->orderBy('warehouse_id');
    }
}
