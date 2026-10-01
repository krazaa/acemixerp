<?php

namespace App\Services\Inventory;

use App\Contracts\StockLedger;
use App\Data\StockMovementData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Placeholder stock ledger for use before Phase 6 is built.
 * Logs movements but does not persist them. Binds only when the
 * real implementation isn't available.
 */
final class NullStockLedger implements StockLedger
{
    public function record(StockMovementData $movement): void
    {
        Log::info('NullStockLedger: movement not persisted', [
            'item_id' => $movement->itemId,
            'warehouse_id' => $movement->warehouseId,
            'quantity' => $movement->quantity,
            'type' => $movement->type->value,
        ]);
    }

    public function recordMany(array $movements): void
    {
        foreach ($movements as $m) {
            $this->record($m);
        }
    }

    public function onHand(int $itemId, int $warehouseId): string
    {
        return '0.0000';
    }

    public function totalOnHand(int $itemId): string
    {
        return '0.0000';
    }

    public function available(int $itemId, int $warehouseId): string
    {
        return '0.0000';
    }

    public function history(int $itemId, ?int $warehouseId = null, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): Collection
    {
        return collect();
    }
}
