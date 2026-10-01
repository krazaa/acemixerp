<?php

declare(strict_types=1);

namespace Modules\FixedAssets\Enums;

enum AssetTransactionType: string
{
    case Capitalization = 'capitalization';
    case Depreciation = 'depreciation';
    case Transfer = 'transfer';
    case Maintenance = 'maintenance';
    case Revaluation = 'revaluation';
    case Disposal = 'disposal';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
