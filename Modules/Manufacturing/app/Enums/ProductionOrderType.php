<?php

namespace Modules\Manufacturing\Enums;

enum ProductionOrderType: string
{
    case Standard = 'standard';       // make to stock
    case MakeToOrder = 'make_to_order';  // tied to a Sales Order

    case Repack = 'repack';         // repackage bulk into retail units

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::MakeToOrder => 'Make to Order',
            self::Repack => 'Repack',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Standard => 'secondary',
            self::MakeToOrder => 'primary',
            self::Repack => 'warning',
        };
    }

    /**
     * Whether this order type must be linked to a Sales Order.
     */
    public function requiresSalesOrder(): bool
    {
        return $this === self::MakeToOrder;
    }

    /**
     * Whether this order type produces finished goods that go to stock
     * for general sale (as opposed to a single committed customer).
     */
    public function isStockBuild(): bool
    {
        return in_array($this, [self::Standard, self::Repack], true);
    }

    public function description(): string
    {
        return match ($this) {
            self::Standard => 'Build finished goods for general stock.',
            self::MakeToOrder => 'Build a product committed to a specific customer order.',
            self::Repack => 'Repackage a bulk item into retail units without further transformation.',
        };
    }
}
