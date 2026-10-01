<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Enums;

enum ProductionOrderStatus: string
{
    case Draft = 'draft';
    case Planned = 'planned';
    case Released = 'released';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Planned => 'Planned',
            self::Released => 'Released',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Planned => 'info',
            self::Released => 'primary',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Closed => 'dark',
            self::Cancelled => 'dark',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Planned], true);
    }

    public function isImmutable(): bool
    {
        return in_array($this, [self::Completed, self::Closed, self::Cancelled], true);
    }

    public function canPlan(): bool
    {
        return $this === self::Draft;
    }

    public function canRelease(): bool
    {
        return in_array($this, [self::Draft, self::Planned], true);
    }

    public function canStart(): bool
    {
        return $this === self::Released;
    }

    public function canComplete(): bool
    {
        return $this === self::InProgress;
    }

    public function canClose(): bool
    {
        return $this === self::Completed;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Draft, self::Planned, self::Released], true);
    }
}
