<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomerStatus: string
{
    case Prospect = 'prospect';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Prospect',
            self::Active => 'Active Customer',
            self::OnHold => 'On Hold',
            self::Inactive => 'Inactive',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Prospect => 'info',
            self::OnHold => 'warning',
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
            self::Prospect => in_array($target, [self::Active, self::Inactive], true),
            self::Active => in_array($target, [self::OnHold, self::Inactive], true),
            self::OnHold => in_array($target, [self::Active, self::Inactive], true),
            self::Inactive => $target === self::Active,
        };
    }
}
