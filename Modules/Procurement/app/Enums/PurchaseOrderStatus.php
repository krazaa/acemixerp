<?php

declare(strict_types=1);

namespace Modules\Procurement\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Issued = 'issued';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Issued => 'Issued',
            self::PartiallyReceived => 'Partially Received',
            self::Received => 'Received',
            self::Closed => 'Closed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Submitted => 'info',
            self::Approved => 'primary',
            self::Issued => 'primary',
            self::PartiallyReceived => 'warning',
            self::Received => 'success',
            self::Closed => 'dark',
            self::Rejected => 'danger',
            self::Cancelled => 'dark',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    public function isImmutable(): bool
    {
        return in_array($this, [
            self::Issued, self::PartiallyReceived, self::Received,
            self::Closed, self::Cancelled,
        ], true);
    }

    public function canSubmit(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    public function canApprove(): bool
    {
        return $this === self::Submitted;
    }

    public function canIssue(): bool
    {
        return $this === self::Approved;
    }

    public function acceptsReceipt(): bool
    {
        return in_array($this, [self::Issued, self::PartiallyReceived], true);
    }

    public function canCancel(): bool
    {
        return in_array($this, [
            self::Draft, self::Submitted, self::Approved, self::Rejected,
        ], true);
    }
}
