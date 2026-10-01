<?php

namespace Modules\Procurement\Enums;

enum RfqStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Receiving = 'receiving';
    case Awarded = 'awarded';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::Receiving => 'Receiving Quotes',
            self::Awarded => 'Awarded',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Issued => 'info',
            self::Receiving => 'warning',
            self::Awarded => 'success',
            self::Closed => 'dark',
            self::Cancelled => 'dark',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function canIssue(): bool
    {
        return $this === self::Draft;
    }

    public function canReceive(): bool
    {
        return in_array($this, [self::Issued, self::Receiving], true);
    }

    public function canAward(): bool
    {
        return in_array($this, [self::Issued, self::Receiving], true);
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Draft, self::Issued, self::Receiving], true);
    }
}
