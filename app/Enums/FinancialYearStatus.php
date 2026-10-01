<?php

namespace App\Enums;

enum FinancialYearStatus: string
{
    case Open = 'open';
    case Closing = 'closing';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Closing => 'Closing',
            self::Closed => 'Closed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'success',
            self::Closing => 'warning',
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
            self::Open => in_array($target, [self::Closing, self::Closed], true),
            self::Closing => $target === self::Closed,
            self::Closed => false,
        };
    }
}
