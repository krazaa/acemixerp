<?php

use App\Data\StockMovementData;
use App\Enums\StockMovementType;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Services\WeightedAverageValuation;

test('prices an inbound adjustment at its supplied unit cost', function () {
    $balance = new StockBalance([
        'quantity' => '10.0000',
        'total_value' => '1000.0000',
    ]);
    $movement = new StockMovementData(
        itemId: 1,
        warehouseId: 1,
        quantity: '5.0000',
        type: StockMovementType::Adjustment,
        occurredAt: now(),
        unitCost: '120.0000',
    );

    [$unitCost, $totalCost, $newValue] = (new WeightedAverageValuation)->applyMovement($balance, $movement);

    expect($unitCost)->toBe('120.0000')
        ->and($totalCost)->toBe('600.0000')
        ->and($newValue)->toBe('1600.0000');
});

test('uses the current weighted average cost for an outbound movement', function () {
    $balance = new StockBalance([
        'quantity' => '10.0000',
        'total_value' => '1000.0000',
    ]);
    $movement = new StockMovementData(
        itemId: 1,
        warehouseId: 1,
        quantity: '-4.0000',
        type: StockMovementType::Consumption,
        occurredAt: now(),
    );

    [$unitCost, $totalCost, $newValue] = (new WeightedAverageValuation)->applyMovement($balance, $movement);

    expect($unitCost)->toBe('100.0000')
        ->and($totalCost)->toBe('400.0000')
        ->and($newValue)->toBe('600.0000');
});
