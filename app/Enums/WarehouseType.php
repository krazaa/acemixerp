<?php

namespace App\Enums;

enum WarehouseType: string
{
    case Main = 'main';        // primary stocking location
    case Distribution = 'distribution'; // regional hub, high-throughput
    case Retail = 'retail';      // customer-facing, small quantities
    case Transit = 'transit';     // in-transfer holding (Phase 6)
    case Quarantine = 'quarantine';  // damaged/QC-hold

    public function label(): string
    {
        return match ($this) {
            self::Main => 'Main Warehouse',
            self::Distribution => 'Distribution Center',
            self::Retail => 'Retail Outlet',
            self::Transit => 'In-Transit',
            self::Quarantine => 'Quarantine',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Main => 'primary',
            self::Distribution => 'info',
            self::Retail => 'success',
            self::Transit => 'warning',
            self::Quarantine => 'danger',
        };
    }

    /**
     * Transit warehouses hold stock that has left the source but not yet been
     * received at destination. They participate in transfers but not in
     * normal sales or purchasing.
     */
    public function isTransit(): bool
    {
        return $this === self::Transit;
    }

    /** Whether this warehouse can be set as the organization default. */
    public function canBeDefault(): bool
    {
        return in_array($this, [self::Main, self::Distribution, self::Retail], true);
    }
}
