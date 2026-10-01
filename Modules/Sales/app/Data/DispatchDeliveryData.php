<?php

declare(strict_types=1);

namespace Modules\Sales\Data;

final readonly class DispatchDeliveryData
{
    public function __construct(
        public string $vehicleNumber,
        public string $driverName,
        public string $driverContact,
        public string $driverCnic,
        public string $biltyNumber,
    ) {}

    /** @param array<string, string> $data */
    public static function fromValidated(array $data): self
    {
        return new self(
            vehicleNumber: $data['vehicle_number'],
            driverName: $data['driver_name'],
            driverContact: $data['driver_contact'],
            driverCnic: $data['driver_cnic'],
            biltyNumber: $data['bilty_number'],
        );
    }
}
