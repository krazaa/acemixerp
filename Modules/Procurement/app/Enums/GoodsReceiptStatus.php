<?php

declare(strict_types=1);

namespace Modules\Procurement\Enums;

enum GoodsReceiptStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Posted => 'Posted',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Posted => 'success',
            self::Cancelled => 'dark',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
