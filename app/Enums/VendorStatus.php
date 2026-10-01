<?php

declare(strict_types=1);

namespace App\Enums;

enum VendorStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Validation',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Inactive => 'Inactive',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Pending => 'info',
            self::Suspended => 'warning',
            self::Inactive => 'secondary',
        };
    }

    public function canTransact(): bool
    {
        return $this === self::Active;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Active, self::Inactive], true),
            self::Active => in_array($target, [self::Suspended, self::Inactive], true),
            self::Suspended => in_array($target, [self::Active, self::Inactive], true),
            self::Inactive => $target === self::Active,
        };
    }
}
