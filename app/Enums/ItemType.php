<?php

namespace App\Enums;

enum ItemType: string
{
    case Stock = 'stock';       // physical, tracked, valued
    case Service = 'service';     // no inventory
    case Asset = 'asset';       // fixed asset, no inventory movement through sales
    case Consumable = 'consumable';  // expensed, sometimes tracked

    public function label(): string
    {
        return match ($this) {
            self::Stock => 'Stock Item',
            self::Service => 'Service',
            self::Asset => 'Fixed Asset',
            self::Consumable => 'Consumable',
        };
    }

    public function tracksInventory(): bool
    {
        return in_array($this, [self::Stock, self::Consumable], true);
    }

    public function isSellable(): bool
    {
        return in_array($this, [self::Stock, self::Service, self::Consumable], true);
    }

    public function isPurchasable(): bool
    {
        return in_array($this, [self::Stock, self::Service, self::Asset, self::Consumable], true);
    }
}
