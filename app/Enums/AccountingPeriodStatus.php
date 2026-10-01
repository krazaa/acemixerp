<?php

namespace App\Enums;

enum AccountingPeriodStatus: string
{
    case Open = 'open';
    case SoftClosed = 'soft_closed';  // adjustments allowed by privileged users
    case Closed = 'closed';       // no posting at all

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::SoftClosed => 'Soft Closed',
            self::Closed => 'Closed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'success',
            self::SoftClosed => 'warning',
            self::Closed => 'secondary',
        };
    }

    public function acceptsPosting(): bool
    {
        return $this === self::Open;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Open => in_array($target, [self::SoftClosed, self::Closed], true),
            self::SoftClosed => in_array($target, [self::Open, self::Closed], true),
            self::Closed => $target === self::SoftClosed, // reopen for adjustment only
        };
    }
}
