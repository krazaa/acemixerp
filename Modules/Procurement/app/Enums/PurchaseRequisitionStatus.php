<?php

namespace Modules\Procurement\Enums;

enum PurchaseRequisitionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Converted = 'converted';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Converted => 'Converted',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Submitted => 'info',
            self::UnderReview => 'warning',
            self::Approved => 'primary',
            self::Rejected => 'danger',
            self::Converted => 'success',
            self::Closed => 'dark',
            self::Cancelled => 'dark',
        };
    }

    /** Editable while draft or rejected. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    public function isImmutable(): bool
    {
        return in_array($this, [
            self::Approved, self::Converted, self::Closed, self::Cancelled,
        ], true);
    }

    public function canSubmit(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    public function canApprove(): bool
    {
        return in_array($this, [self::Submitted, self::UnderReview], true);
    }

    public function canConvert(): bool
    {
        return $this === self::Approved;
    }

    public function canCancel(): bool
    {
        return in_array($this, [
            self::Draft, self::Submitted, self::UnderReview, self::Approved, self::Rejected,
        ], true);
    }
}
