<?php

namespace Modules\Procurement\Enums;

enum RfqVendorStatus: string
{
    case Invited = 'invited';
    case Submitted = 'submitted';
    case Rejected = 'rejected';
    case Awarded = 'awarded';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Invited => 'Invited',
            self::Submitted => 'Submitted',
            self::Rejected => 'Rejected',
            self::Awarded => 'Awarded',
            self::Declined => 'Declined',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Invited => 'secondary',
            self::Submitted => 'info',
            self::Rejected => 'danger',
            self::Awarded => 'success',
            self::Declined => 'dark',
        };
    }
}
