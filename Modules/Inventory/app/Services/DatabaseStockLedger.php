<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Contracts\StockLedger;
use App\Data\StockMovementData;
use App\Exceptions\BusinessRuleException;
use App\Models\Item;
use App\Models\Organization;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockMovement;

final class DatabaseStockLedger implements StockLedger
{
    public function __construct(
        private readonly InventoryValuationFactory $valuationFactory,
    ) {}

    public function record(StockMovementData $movement): void
    {
        $this->recordMany([$movement]);
    }

    public function recordMany(array $movements): void
    {
        if (empty($movements)) {
            return;
        }

        DB::transaction(function () use ($movements) {
            // Group by item+warehouse+batch+serial so we lock each balance once
            // and process movements for the same combination sequentially.
            $groups = [];
            foreach ($movements as $m) {
                $key = $m->itemId.'|'.$m->warehouseId.'|'
                     .($m->batchId ?? 'null').'|'.($m->serialNumber ?? 'null');
                $groups[$key][] = $m;
            }

            foreach ($groups as $group) {
                foreach ($group as $movement) {
                    $this->recordOne($movement);
                }
            }
        });
    }

    public function onHand(int $itemId, int $warehouseId): string
    {
        $sum = StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity');

        return $sum ? (string) $sum : '0.0000';
    }

    public function totalOnHand(int $itemId): string
    {
        $sum = StockBalance::query()
            ->where('item_id', $itemId)
            ->sum('quantity');

        return $sum ? (string) $sum : '0.0000';
    }

    public function available(int $itemId, int $warehouseId): string
    {
        $row = StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->selectRaw('COALESCE(SUM(quantity - reserved_quantity), 0) AS available')
            ->first();

        return $row?->available !== null ? (string) $row->available : '0.0000';
    }

    public function history(
        int $itemId,
        ?int $warehouseId = null,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
    ): Collection {
        return StockMovement::query()
            ->where('item_id', $itemId)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($from, fn ($q) => $q->where('occurred_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('occurred_at', '<=', $to))
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    // ─── Internal ────────────────────────────────────────────────────

    private function recordOne(StockMovementData $movement): void
    {
        // 1. Idempotency: same source + item + warehouse + type already recorded?
        if ($movement->sourceType && $movement->sourceId) {
            $exists = StockMovement::query()
                ->where('source_type', $movement->sourceType)
                ->where('source_id', $movement->sourceId)
                ->where('item_id', $movement->itemId)
                ->where('warehouse_id', $movement->warehouseId)
                ->where('batch_id', $movement->batchId)
                ->where('serial_number', $movement->serialNumber)
                ->where('type', $movement->type->value)
                ->exists();

            if ($exists) {
                return;
            } // silently skip duplicates for safety
        }

        // 2. Lock or create the balance row.
        $balance = StockBalance::query()
            ->where('item_id', $movement->itemId)
            ->where('warehouse_id', $movement->warehouseId)
            ->where('batch_id', $movement->batchId)
            ->where('serial_number', $movement->serialNumber)
            ->lockForUpdate()
            ->first();

        if (! $balance) {
            $balance = StockBalance::query()->create([
                'item_id' => $movement->itemId,
                'warehouse_id' => $movement->warehouseId,
                'batch_id' => $movement->batchId,
                'serial_number' => $movement->serialNumber,
                'quantity' => '0.0000',
                'total_value' => '0.0000',
            ]);
        }

        // 3. Negative-stock guard.
        $newQuantity = bcadd((string) $balance->quantity, $movement->quantity, 4);

        if (bccomp($newQuantity, '0', 4) < 0) {
            $item = Item::query()->find($movement->itemId);
            $warehouse = Warehouse::query()->find($movement->warehouseId);
            $org = Organization::current();

            $allowNegative = ($item?->allow_negative_stock ?? false)
                && ($warehouse?->allow_negative_stock ?? false);

            if (! $allowNegative) {
                throw BusinessRuleException::make(
                    "Insufficient stock for {$item?->name} in {$warehouse?->name}. "
                  ."On hand {$balance->quantity}, requested {$movement->quantity}."
                );
            }
        }

        // 4. Cost basis.
        [$unitCost, $totalCost, $newValue] = $this->valuationFactory
            ->forCurrentOrganization()
            ->applyMovement($balance, $movement);

        // 5. Write the movement row with running balance snapshot.
        StockMovement::query()->create([
            'item_id' => $movement->itemId,
            'warehouse_id' => $movement->warehouseId,
            'batch_id' => $movement->batchId,
            'serial_number' => $movement->serialNumber,
            'quantity' => $movement->quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'balance_quantity' => $newQuantity,
            'balance_value' => $newValue,
            'type' => $movement->type,
            'occurred_at' => $movement->occurredAt,
            'reference' => $movement->reference,
            'notes' => $movement->notes,
            'source_type' => $movement->sourceType,
            'source_id' => $movement->sourceId,
            'created_by' => auth()->id(),
        ]);

        // 6. Update the balance row.
        $balance->forceFill([
            'quantity' => $newQuantity,
            'total_value' => $newValue,
            'last_movement_at' => $movement->occurredAt,
        ])->save();
    }
}
