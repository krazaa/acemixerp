<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Locked = 'locked';
    case Disabled = 'disabled';

    public function canLogin(): bool
    {
        return $this === self::Active;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Activation',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Locked => 'Locked',
            self::Disabled => 'Disabled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Pending => 'info',
            self::Suspended => 'warning',
            self::Locked => 'danger',
            self::Disabled => 'secondary',
        };
    }

    /** Valid transitions per §7 lifecycle. */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Active, self::Disabled], true),
            self::Active => in_array($target, [self::Suspended, self::Locked, self::Disabled], true),
            self::Suspended => in_array($target, [self::Active, self::Disabled], true),
            self::Locked => in_array($target, [self::Active, self::Disabled], true),
            self::Disabled => false,
        };
    }
}
