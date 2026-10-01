<?php

namespace Modules\Inventory\Services;

use App\Enums\UserStatus;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\ApplicationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Models\StockBalance;

class LowStockNotificationService
{
    public function check(int $itemId, int $warehouseId): int
    {
        if (! Schema::hasTable('inventory_alert_states') || ! Schema::hasTable('notifications')) {
            return 0;
        }

        return DB::transaction(function () use ($itemId, $warehouseId): int {
            $item = Item::query()->find($itemId);
            $warehouse = Warehouse::query()->find($warehouseId);
            if (! $item || ! $warehouse) {
                return 0;
            }
            DB::table('inventory_alert_states')->insertOrIgnore([
                'item_id' => $itemId, 'warehouse_id' => $warehouseId, 'is_low' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $state = DB::table('inventory_alert_states')->where('item_id', $itemId)->where('warehouse_id', $warehouseId)->lockForUpdate()->first();
            $quantity = (string) StockBalance::query()->where('item_id', $itemId)->where('warehouse_id', $warehouseId)->sum('quantity');
            $threshold = (string) $item->reorder_level;
            $isLow = $item->getRawOriginal('status') === 'active'
                && $warehouse->getRawOriginal('status') === 'active'
                && bccomp($threshold, '0', 4) > 0 && bccomp($quantity, $threshold, 4) <= 0;
            if (! $isLow) {
                DB::table('inventory_alert_states')->where('id', $state->id)->update(['is_low' => false, 'updated_at' => now()]);

                return 0;
            }
            if ($state->is_low) {
                return 0;
            }
            $sent = 0;
            User::query()->where('status', UserStatus::Active->value)->with(['roles.permissions', 'permissions'])
                ->chunkById(100, function ($users) use ($item, $warehouse, $quantity, $threshold, &$sent): void {
                    foreach ($users as $user) {
                        if ($user->can('inventory.view')) {
                            $user->notify(new ApplicationNotification(
                                'Low stock: '.$item->name,
                                $item->code.' in '.$warehouse->name.': '.$quantity.' on hand; reorder level '.$threshold.'.',
                                route('inventory.stock.show', ['item' => $item->id, 'warehouse_id' => $warehouse->id], false),
                                'inventory',
                            ));
                            $sent++;
                        }
                    }
                });
            if ($sent > 0) {
                DB::table('inventory_alert_states')->where('id', $state->id)->update(['is_low' => true, 'updated_at' => now()]);
            }

            return $sent;
        });
    }
}
