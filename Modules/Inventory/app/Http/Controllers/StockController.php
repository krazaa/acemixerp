<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Contracts\ItemManager;
use App\Contracts\StockLedger;
use App\Contracts\WarehouseManager;
use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockMovement;

class StockController extends Controller
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly WarehouseManager $warehouses,
        private readonly ItemManager $items,
    ) {}

    /**
     * On-hand stock dashboard: one row per item × warehouse combination.
     * Stock balances are stored per batch and serial, so their quantities and
     * values are aggregated before they are displayed here.
     */
    public function index(Request $request): View
    {
        $this->authorize('inventory.view');

        $query = StockBalance::query()
            ->selectRaw('MIN(stock_balances.id) as id, stock_balances.item_id as item_id, stock_balances.warehouse_id as warehouse_id')
            ->selectRaw('SUM(stock_balances.quantity) as quantity')
            ->selectRaw('SUM(stock_balances.total_value) as total_value')
            ->selectRaw('SUM(stock_balances.reserved_quantity) as reserved_quantity')
            ->with(['item:id,code,name,unit_id,item_type,reorder_level,minimum_stock', 'item.unit:id,code,name', 'warehouse:id,code,name'])
            ->when($request->integer('warehouse_id'), fn ($q, $w) => $q->where('warehouse_id', $w))
            ->when($request->integer('item_id'), fn ($q, $i) => $q->where('item_id', $i))
            ->when($request->string('search')->toString(), function ($q, $s) {
                $q->whereHas('item', fn ($q) => $q
                    ->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%"));
            })
            ->when($request->boolean('low_stock'), function ($q) {
                $q->whereHas('item', fn ($q) => $q
                    ->where('items.reorder_level', '>', 0))
                    ->havingRaw('SUM(stock_balances.quantity) <= MAX(items.reorder_level)');
            })
            ->join('items', 'items.id', '=', 'stock_balances.item_id')
            ->groupBy('stock_balances.item_id', 'stock_balances.warehouse_id')
            ->orderBy('stock_balances.item_id')
            ->orderBy('stock_balances.warehouse_id');

        $totalValue = DB::query()
            ->fromSub((clone $query)->toBase(), 'aggregated_balances')
            ->sum('total_value');

        $balances = $request->boolean('print')
            ? $query->get()
            : $query->paginate(50)->withQueryString();

        return view('inventory::stock.index', [
            'balances' => $balances,
            'warehouses' => $this->warehouses->allActive(),
            'items' => $this->items->allSellable(),
            'totalValue' => (string) $totalValue,
        ]);
    }

    /**
     * Item detail: current balances per warehouse, recent movements,
     * reorder thresholds.
     */
    public function show(Request $request, Item $item): View
    {
        $this->authorize('inventory.view');

        $balances = StockBalance::query()
            ->with('warehouse:id,code,name')
            ->where('item_id', $item->id)
            ->orderBy('warehouse_id')
            ->get();

        $movements = StockMovement::query()
            ->with(['warehouse:id,code,name', 'creator:id,name'])
            ->where('item_id', $item->id)
            ->when($request->integer('warehouse_id'), fn ($q, $w) => $q->where('warehouse_id', $w))
            ->when($request->date('from'), fn ($q, $d) => $q->where('occurred_at', '>=', $d))
            ->when($request->date('to'), fn ($q, $d) => $q->where('occurred_at', '<=', $d))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $totalOnHand = $this->ledger->totalOnHand($item->id);
        $totalValue = $balances->sum('total_value');

        return view('inventory::stock.show', [
            'item' => $item->load(['unit:id,code,name', 'category:id,code,name']),
            'balances' => $balances,
            'movements' => $movements,
            'warehouses' => $this->warehouses->allActive(),
            'totalOnHand' => $totalOnHand,
            'totalValue' => (string) $totalValue,
        ]);
    }
}
