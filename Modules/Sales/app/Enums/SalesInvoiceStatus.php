<?php

namespace Modules\Sales\Enums;

enum SalesInvoiceStatus: string
{
    case Draft = 'draft';
    case Matched = 'matched';
    case Mismatch = 'mismatch';
    case Approved = 'approved';
    case Posted = 'posted';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Matched => 'Matched',
            self::Mismatch => 'Mismatch',
            self::Approved => 'Approved',
            self::Posted => 'Posted',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid => 'Paid',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Reversed => 'Reversed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Matched => 'info',
            self::Mismatch => 'warning',
            self::Approved => 'primary',
            self::Posted => 'success',
            self::PartiallyPaid => 'primary',
            self::Paid => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'dark',
            self::Reversed => 'warning',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Mismatch, self::Rejected], true);
    }

    public function isImmutable(): bool
    {
        return in_array($this, [
            self::Posted, self::PartiallyPaid, self::Paid,
            self::Cancelled, self::Reversed,
        ], true);
    }

    public function canMatch(): bool
    {
        return $this === self::Draft;
    }

    public function canApprove(): bool
    {
        return in_array($this, [self::Matched, self::Mismatch], true);
    }

    public function canPost(): bool
    {
        return $this === self::Approved;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Draft, self::Matched, self::Mismatch, self::Rejected], true);
    }

    public function canReverse(): bool
    {
        return $this === self::Posted;
    }

    public function acceptsPayment(): bool
    {
        return in_array($this, [self::Posted, self::PartiallyPaid], true);
    }
}
