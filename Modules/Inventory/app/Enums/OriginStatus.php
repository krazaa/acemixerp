<?php

namespace Modules\Inventory\Enums;

enum OriginStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Archived => 'Archived',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'warning',
            self::Archived => 'secondary',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Active => in_array($target, [self::Inactive, self::Archived], true),
            self::Inactive => in_array($target, [self::Active, self::Archived], true),
            self::Archived => false,
        };
    }

    /** Rows in these states are selectable in transactional forms. */
    public function isSelectable(): bool
    {
        return $this === self::Active;
    }
}
