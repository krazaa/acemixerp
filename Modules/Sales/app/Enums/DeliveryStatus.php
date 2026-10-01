<?php

namespace Modules\Sales\Enums;

enum DeliveryStatus: string
{
    case Draft = 'draft';
    case Picked = 'picked';
    case Dispatched = 'dispatched';
    case Delivered = 'delivered';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Picked => 'Picked',
            self::Dispatched => 'Dispatched',
            self::Delivered => 'Delivered',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Picked => 'info',
            self::Dispatched => 'primary',
            self::Delivered => 'success',
            self::Closed => 'dark',
            self::Cancelled => 'dark',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft], true);
    }

    public function isImmutable(): bool
    {
        return in_array($this, [
            self::Dispatched, self::Delivered,
            self::Closed, self::Cancelled,
        ], true);
    }

    public function canPick(): bool
    {
        return $this === self::Draft;
    }

    public function canDispatch(): bool
    {
        return in_array($this, [self::Draft, self::Picked], true);
    }

    public function canMarkDelivered(): bool
    {
        return $this === self::Dispatched;
    }

    public function canClose(): bool
    {
        return $this === self::Delivered;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Draft, self::Picked], true);
    }
}
