<?php

use Modules\Sales\Data\DispatchDeliveryData;

test('provides dispatch details to the delivery workflow', function () {
    $details = DispatchDeliveryData::fromValidated([
        'vehicle_number' => 'ABC-123',
        'driver_name' => 'Ali Khan',
        'driver_contact_number' => '+92 300 1234567',
        'driver_cnic' => '3520212345671',
        'bilty_number' => 'BILTY-001',
    ]);

    expect($details->vehicleNumber)->toBe('ABC-123')
        ->and($details->driverName)->toBe('Ali Khan')
        ->and($details->driverContactNumber)->toBe('+92 300 1234567')
        ->and($details->driverCnic)->toBe('3520212345671')
        ->and($details->biltyNumber)->toBe('BILTY-001');
});
