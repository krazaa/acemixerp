<?php

namespace Modules\Sales\Enums;

enum SalesOrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case OnHold = 'on_hold';
    case Confirmed = 'confirmed';
    case PartiallyDelivered = 'partially_delivered';
    case Delivered = 'delivered';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::OnHold => 'Credit Hold',
            self::Confirmed => 'Confirmed',
            self::PartiallyDelivered => 'Partially Delivered',
            self::Delivered => 'Delivered',
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
            self::OnHold => 'warning',
            self::Confirmed => 'primary',
            self::PartiallyDelivered => 'warning',
            self::Delivered => 'success',
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
            self::Confirmed, self::PartiallyDelivered,
            self::Delivered, self::Closed, self::Cancelled,
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

    public function canReleaseHold(): bool
    {
        return $this === self::OnHold;
    }

    public function canConfirm(): bool
    {
        return $this === self::Approved;
    }

    public function acceptsDelivery(): bool
    {
        return in_array($this, [self::Confirmed, self::PartiallyDelivered], true);
    }

    public function canCancel(): bool
    {
        return in_array($this, [
            self::Draft, self::Submitted, self::Approved,
            self::OnHold, self::Rejected,
        ], true);
    }
}
