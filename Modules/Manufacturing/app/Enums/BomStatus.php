<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Enums;

enum BomStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Superseded = 'superseded';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Superseded => 'Superseded',
            self::Archived => 'Archived',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Active => 'success',
            self::Superseded => 'warning',
            self::Archived => 'dark',
        };
    }
}
