<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Services\LowStockNotificationService;

class NotifyLowStock extends Command
{
    protected $signature = 'inventory:notify-low-stock';

    protected $description = 'Notify inventory users about new low-stock conditions by item and warehouse';

    public function handle(LowStockNotificationService $alerts): int
    {
        $sent = 0;
        $pairs = StockBalance::query()->select(['item_id', 'warehouse_id'])
            ->groupBy('item_id', 'warehouse_id')->orderBy('item_id')->orderBy('warehouse_id')->lazy(200);
        foreach ($pairs as $pair) {
            $sent += $alerts->check($pair->item_id, $pair->warehouse_id);
        }
        $this->info("Sent {$sent} low-stock notifications.");

        return self::SUCCESS;
    }
}
