<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Contracts\WarehouseManager;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Models\StockMovement;

class StockMovementController extends Controller
{
    public function __construct(private readonly WarehouseManager $warehouses) {}

    public function index(Request $request): View
    {
        $this->authorize('inventory.view');

        $query = StockMovement::query()
            ->with([
                'item:id,code,name',
                'warehouse:id,code,name',
                'creator:id,name',
            ])
            ->when($request->integer('item_id'), fn ($q, $i) => $q->where('item_id', $i))
            ->when($request->integer('warehouse_id'), fn ($q, $w) => $q->where('warehouse_id', $w))
            ->when($request->string('type')->toString(), fn ($q, $t) => $q->where('type', $t))
            ->when($request->string('reference')->toString(), fn ($q, $r) => $q->where('reference', 'like', "%{$r}%"))
            ->when($request->date('from'), fn ($q, $d) => $q->where('occurred_at', '>=', $d))
            ->when($request->date('to'), fn ($q, $d) => $q->where('occurred_at', '<=', $d))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        $movements = $request->boolean('print')
            ? $query->get()
            : $query->paginate(50)->withQueryString();

        return view('inventory::movements.index', [
            'movements' => $movements,
            'warehouses' => $this->warehouses->allActive(),
            'types' => StockMovementType::cases(),
        ]);
    }
}
