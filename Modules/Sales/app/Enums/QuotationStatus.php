<?php

namespace Modules\Sales\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Converted = 'converted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
            self::Converted => 'Converted',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Sent => 'info',
            self::Accepted => 'primary',
            self::Rejected => 'danger',
            self::Expired => 'warning',
            self::Converted => 'success',
            self::Cancelled => 'dark',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    public function isImmutable(): bool
    {
        return in_array($this, [self::Sent, self::Converted, self::Cancelled], true);
    }

    public function canSend(): bool
    {
        return $this === self::Draft;
    }

    public function canAccept(): bool
    {
        return $this === self::Sent;
    }

    public function canReject(): bool
    {
        return $this === self::Sent;
    }

    public function canConvert(): bool
    {
        return $this === self::Accepted;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Rejected], true);
    }
}
