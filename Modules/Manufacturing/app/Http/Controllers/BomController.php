<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Http\Controllers;

use App\Contracts\ItemManager;
use App\Contracts\UnitManager;
use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Inventory\Models\StockBalance;
use Modules\Manufacturing\Contracts\BomManager;
use Modules\Manufacturing\Data\BomData;
use Modules\Manufacturing\Enums\BomStatus;
use Modules\Manufacturing\Http\Requests\Boms\StoreBomRequest;
use Modules\Manufacturing\Http\Requests\Boms\UpdateBomRequest;
use Modules\Manufacturing\Models\BillOfMaterials;

class BomController extends Controller
{
    public function __construct(
        private readonly BomManager $boms,
        private readonly ItemManager $items,
        private readonly UnitManager $units,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BillOfMaterials::class);

        return view('manufacturing::boms.index', [
            'boms' => $this->boms->paginate(
                $request->only(['search', 'status', 'product_id'])
            ),
            'products' => $this->items->allSellable(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', BillOfMaterials::class);

        return view('manufacturing::boms.create', [
            'bom' => new BillOfMaterials([
                'output_quantity' => 1,
                'revision' => 1,
                'status' => BomStatus::Draft,
            ]),
            'products' => $this->items->allSellable(),
            'components' => $this->items->allPurchasable(),
            'units' => $this->units->allActive(),
        ]);
    }

    public function store(StoreBomRequest $request): RedirectResponse
    {
        $bom = $this->boms->create(
            BomData::fromRequest($request),
            $request->user()->id,
        );

        return redirect()
            ->route('manufacturing.boms.show', $bom)
            ->with('status', "BOM {$bom->code} created as draft.");
    }

    public function show(BillOfMaterials $bom): View
    {
        $this->authorize('view', $bom);

        return view('manufacturing::boms.show', [
            'bom' => $bom->load([
                'lines.component', 'lines.batch', 'lines.unit',
                'product', 'outputUnit',
                'creator', 'updater', 'supersededBy',
            ]),
            'estimatedMaterials' => $bom->estimatedMaterialsCost(),
            'estimatedUnitCost' => $bom->estimatedUnitCost(),
        ]);
    }

    public function edit(BillOfMaterials $bom): View
    {
        $this->authorize('update', $bom);

        return view('manufacturing::boms.edit', [
            'bom' => $bom->load('lines'),
            'products' => $this->items->allSellable(),
            'components' => $this->items->allPurchasable(),
            'units' => $this->units->allActive(),
        ]);
    }

    public function update(UpdateBomRequest $request, BillOfMaterials $bom): RedirectResponse
    {
        $this->boms->update($bom, BomData::fromRequest($request), $request->user()->id);

        return redirect()
            ->route('manufacturing.boms.show', $bom)
            ->with('status', 'BOM updated.');
    }

    public function destroy(BillOfMaterials $bom): RedirectResponse
    {
        $this->authorize('delete', $bom);

        $this->boms->delete($bom);

        return redirect()
            ->route('manufacturing.boms.index')
            ->with('status', 'BOM deleted.');
    }

    public function activate(BillOfMaterials $bom): RedirectResponse
    {
        $this->authorize('update', $bom);

        $this->boms->activate($bom, auth()->id());

        return back()->with('status', 'BOM activated.');
    }

    public function ingredientBatches(Item $ingredient): JsonResponse
    {
        $this->authorize('viewAny', BillOfMaterials::class);

        $batches = StockBalance::query()
            ->where('item_id', $ingredient->id)
            ->whereNotNull('batch_id')
            ->with(['batch:id,item_id,warehouse_id,number,expiry_date', 'warehouse:id,name'])
            ->orderBy('batch_id')
            ->get(['id', 'warehouse_id', 'batch_id', 'quantity', 'reserved_quantity'])
            ->filter(fn (StockBalance $balance): bool => $balance->batch !== null)
            ->unique('batch_id')
            ->values()
            ->map(fn (StockBalance $balance): array => [
                'id' => $balance->batch_id,
                'number' => $balance->batch->number,
                'expiry' => $balance->batch->expiry_date,
                'warehouse' => $balance->warehouse?->name,
                'available_quantity' => $balance->availableQuantity(),
            ]);

        return response()->json(['data' => $batches]);
    }
}
