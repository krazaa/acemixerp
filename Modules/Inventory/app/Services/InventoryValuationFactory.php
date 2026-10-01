<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Models\Organization;
use Modules\Inventory\Contracts\InventoryValuation;

final class InventoryValuationFactory
{
    public function forCurrentOrganization(): InventoryValuation
    {
        $method = Organization::current()->inventory_valuation_method ?? 'weighted_average';

        return match ($method) {
            'fifo' => new FifoValuation,
            default => new WeightedAverageValuation,
        };
    }
}
