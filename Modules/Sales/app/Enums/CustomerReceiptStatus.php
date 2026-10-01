<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum CustomerReceiptStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Posted = 'posted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Posted => 'Posted',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Reversed => 'Reversed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Submitted => 'info',
            self::Approved => 'primary',
            self::Posted => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'dark',
            self::Reversed => 'warning',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    public function isImmutable(): bool
    {
        return in_array($this, [self::Posted, self::Cancelled, self::Reversed], true);
    }

    public function canSubmit(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    public function canApprove(): bool
    {
        return $this === self::Submitted;
    }

    public function canPost(): bool
    {
        return $this === self::Approved;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Draft, self::Submitted, self::Approved, self::Rejected], true);
    }

    public function canReverse(): bool
    {
        return $this === self::Posted;
    }
}
