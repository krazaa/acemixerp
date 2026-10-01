<?php

use Modules\Sales\Models\Delivery;
use Tests\TestCase;

uses(TestCase::class);

test('recognizes a dispatch from its timestamp even when the status is stale', function () {
    $delivery = new Delivery([
        'dispatched_at' => '2026-09-17 11:13:03',
    ]);

    expect($delivery->hasBeenDispatched())->toBeTrue()
        ->and($delivery->isFullyDispatched())->toBeTrue();
});

test('does not treat an undelivered draft as dispatched', function () {
    $delivery = new Delivery;

    expect($delivery->hasBeenDispatched())->toBeFalse()
        ->and($delivery->isFullyDispatched())->toBeFalse();
});
