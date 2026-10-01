<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use App\Data\StockMovementData;
use Modules\Inventory\Models\StockBalance;

interface InventoryValuation
{
    /** @return array{0: string, 1: string, 2: string} [unitCost, totalCost, newBalanceValue] */
    public function applyMovement(StockBalance $balance, StockMovementData $movement): array;
}
