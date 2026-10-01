<?php

namespace App\Contracts;

use App\Data\StockMovementData;
use App\Exceptions\BusinessRuleException;
use App\Models\Warehouse;
use Illuminate\Support\Collection;

interface StockLedger
{
    /**
     * Record a single stock movement. Writes the movement row and updates
     * the cached `stock_balances` table atomically. Must be called inside
     * an outer DB::transaction by the caller when multiple movements are
     * related to one document.
     *
     * @throws BusinessRuleException when negative stock is disallowed
     */
    public function record(StockMovementData $movement): void;

    /**
     * Record many movements in a single transaction. Either all succeed or
     * none are persisted.
     *
     * @param  StockMovementData[]  $movements
     */
    public function recordMany(array $movements): void;

    /** Current on-hand quantity for an item in a warehouse. */
    public function onHand(int $itemId, int $warehouseId): string;

    /** Total on-hand across all warehouses for an item. */
    public function totalOnHand(int $itemId): string;

    /** Available = onHand − reserved. */
    public function available(int $itemId, int $warehouseId): string;

    /**
     * Movement history for an item in a warehouse.
     *
     * @return Collection<int, object>
     */
    public function history(int $itemId, ?int $warehouseId = null, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): Collection;
}
