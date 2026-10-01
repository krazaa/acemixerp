<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Data\StockMovementData;
use Modules\Inventory\Contracts\InventoryValuation;
use Modules\Inventory\Models\StockBalance;

final class WeightedAverageValuation implements InventoryValuation
{
    public function applyMovement(StockBalance $balance, StockMovementData $movement): array
    {
        $currentQuantity = (string) $balance->quantity;
        $currentValue = (string) $balance->total_value;

        if ($movement->isInbound()) {
            $unitCost = $movement->unitCost ?? '0.0000';
            $totalCost = bcmul($movement->quantity, $unitCost, 4);
            $newValue = bcadd($currentValue, $totalCost, 4);

            return [$unitCost, $totalCost, $newValue];
        }

        $unitCost = $balance->averageUnitCost();
        $totalCost = bcmul($movement->absoluteQuantity(), $unitCost, 4);
        $newValue = bcsub($currentValue, $totalCost, 4);
        $newQuantity = bcadd($currentQuantity, $movement->quantity, 4);

        if (bccomp($newQuantity, '0', 4) === 0) {
            $newValue = '0.0000';
        }

        return [$unitCost, $totalCost, $newValue];
    }
}
