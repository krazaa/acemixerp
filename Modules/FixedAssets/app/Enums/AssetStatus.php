<?php

declare(strict_types=1);

namespace Modules\FixedAssets\Enums;

enum AssetStatus: string
{
    case Draft = 'draft';
    case Capitalized = 'capitalized';
    case Disposed = 'disposed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Capitalized => 'Capitalized',
            self::Disposed => 'Disposed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Capitalized => 'success',
            self::Disposed => 'dark',
        };
    }
}
