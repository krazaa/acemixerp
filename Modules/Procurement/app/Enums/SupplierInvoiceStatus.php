<?php

namespace Modules\Procurement\Enums;

enum SupplierInvoiceStatus: string
{
    case Draft = 'draft';
    case Matched = 'matched';
    case Mismatch = 'mismatch';       // 3-way match failed, override required
    case Approved = 'approved';
    case Posted = 'posted';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Disputed = 'disputed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Matched => 'Matched',
            self::Mismatch => 'Price/Qty Mismatch',
            self::Approved => 'Approved',
            self::Posted => 'Posted',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid => 'Paid',
            self::Rejected => 'Rejected',
            self::Disputed => 'Disputed',
            self::Cancelled => 'Cancelled',
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
            self::Disputed => 'danger',
            self::Cancelled => 'dark',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Mismatch, self::Rejected], true);
    }

    public function isImmutable(): bool
    {
        return in_array($this, [self::Posted, self::PartiallyPaid, self::Paid, self::Cancelled], true);
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
}
