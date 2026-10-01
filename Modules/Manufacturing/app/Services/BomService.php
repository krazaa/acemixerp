<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Services;

use App\Contracts\SequenceGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Manufacturing\Contracts\BomManager;
use Modules\Manufacturing\Data\BomData;
use Modules\Manufacturing\Enums\BomStatus;
use Modules\Manufacturing\Exceptions\ManufacturingException;
use Modules\Manufacturing\Models\BillOfMaterials;

final class BomService implements BomManager
{
    public function __construct(private readonly SequenceGenerator $sequences) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return BillOfMaterials::query()
            ->with(['product:id,code,name', 'outputUnit:id,code,name'])
            ->withCount('lines')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(
                fn ($q) => $q->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
            ))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['product_id'] ?? null, fn ($q, $p) => $q->where('product_id', $p))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(): Collection
    {
        return BillOfMaterials::query()
            ->active()
            ->with('product:id,code,name')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'product_id', 'output_quantity', 'revision']);
    }

    public function create(BomData $data, int $userId): BillOfMaterials
    {
        return DB::transaction(function () use ($data, $userId) {
            $bom = BillOfMaterials::query()->create([
                'code' => $data->code,
                'name' => $data->name,
                'revision' => $data->revision,
                'product_id' => $data->productId,
                'output_quantity' => $data->outputQuantity,
                'output_unit_id' => $data->outputUnitId,
                'labour_cost' => $data->labourCost,
                'overhead_cost' => $data->overheadCost,
                'notes' => $data->notes,
                'status' => $data->status,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data->lines as $line) {
                $bom->lines()->create($line->toArray());
            }

            return $bom->fresh(['lines.component', 'product']);
        });
    }

    public function update(BillOfMaterials $bom, BomData $data, int $userId): BillOfMaterials
    {
        return DB::transaction(function () use ($bom, $data, $userId) {
            if ($bom->status === BomStatus::Superseded) {
                throw new ManufacturingException("BOM {$bom->code} has been superseded and cannot be edited.");
            }

            $bom->lines()->delete();

            foreach ($data->lines as $line) {
                $bom->lines()->create($line->toArray());
            }

            $bom->fill([
                'code' => $data->code,
                'name' => $data->name,
                'revision' => $data->revision,
                'product_id' => $data->productId,
                'output_quantity' => $data->outputQuantity,
                'output_unit_id' => $data->outputUnitId,
                'labour_cost' => $data->labourCost,
                'overhead_cost' => $data->overheadCost,
                'notes' => $data->notes,
                'status' => $data->status,
                'updated_by' => $userId,
            ])->save();

            return $bom->fresh(['lines.component']);
        });
    }

    public function delete(BillOfMaterials $bom): void
    {
        if ($bom->status === BomStatus::Active) {
            throw new ManufacturingException('Active BOMs cannot be deleted. Archive or supersede them instead.');
        }

        if ($bom->productionOrders()->exists()) {
            throw new ManufacturingException('BOM has been used by a production order and cannot be deleted.');
        }

        DB::transaction(fn () => $bom->delete());
    }

    public function activate(BillOfMaterials $bom, int $userId): BillOfMaterials
    {
        return DB::transaction(function () use ($bom, $userId) {
            // Supersede any other active BOM for the same product.
            BillOfMaterials::query()
                ->where('product_id', $bom->product_id)
                ->where('id', '!=', $bom->id)
                ->where('status', BomStatus::Active->value)
                ->update(['status' => BomStatus::Superseded->value]);

            $bom->forceFill([
                'status' => BomStatus::Active,
                'updated_by' => $userId,
            ])->save();

            return $bom->fresh();
        });
    }
}
