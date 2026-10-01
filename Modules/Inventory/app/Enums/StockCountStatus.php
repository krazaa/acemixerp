<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum StockCountStatus: string
{
    case Draft = 'draft';
    case Counting = 'counting';
    case Review = 'review';
    case Approved = 'approved';
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Counting => 'Counting in Progress',
            self::Review => 'Under Review',
            self::Approved => 'Approved',
            self::Posted => 'Posted',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Counting => 'info',
            self::Review => 'warning',
            self::Approved => 'primary',
            self::Posted => 'success',
            self::Cancelled => 'dark',
        };
    }

    public function canStart(): bool
    {
        return $this === self::Draft;
    }

    public function canSubmit(): bool
    {
        return $this === self::Counting;
    }

    public function canApprove(): bool
    {
        return $this === self::Review;
    }

    public function canPost(): bool
    {
        return $this === self::Approved;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Draft, self::Counting, self::Review, self::Approved], true);
    }
}
