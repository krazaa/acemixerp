<?php

namespace Modules\Inventory\Observers;

use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Services\LowStockNotificationService;

class StockBalanceNotificationObserver
{
    public function __construct(private readonly LowStockNotificationService $alerts) {}

    public function updated(StockBalance $balance): void
    {
        if ($balance->wasChanged('quantity')) {
            $this->alerts->check($balance->item_id, $balance->warehouse_id);
        }
    }
}
