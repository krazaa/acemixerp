<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Contracts\SequenceGenerator;
use App\Contracts\StockLedger;
use App\Exceptions\BusinessRuleException;
use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Contracts\StockAdjustmentManager;
use Modules\Inventory\Contracts\StockCountManager;
use Modules\Inventory\Data\StockAdjustmentData;
use Modules\Inventory\Data\StockAdjustmentLineData;
use Modules\Inventory\Data\StockCountData;
use Modules\Inventory\Enums\StockCountStatus;
use Modules\Inventory\Models\StockCount;

final class StockCountService implements StockCountManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly StockLedger $ledger,
        private readonly StockAdjustmentManager $adjustments,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return StockCount::query()
            ->with(['warehouse:id,code,name', 'creator:id,name'])
            ->withCount('lines')
            ->withSum('lines', 'variance')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('number', 'like', "%{$s}%"))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['warehouse_id'] ?? null, fn ($q, $w) => $q->where('warehouse_id', $w))
            ->orderByDesc('count_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(StockCountData $data, int $userId): StockCount
    {
        return DB::transaction(function () use ($data, $userId) {
            $count = StockCount::query()->create([
                'number' => $this->sequences->next('stock_count', (int) $data->countDate->format('Y')),
                'warehouse_id' => $data->warehouseId,
                'count_date' => $data->countDate,
                'scope' => $data->scope,
                'notes' => $data->notes,
                'status' => StockCountStatus::Draft,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            return $count->fresh('warehouse');
        });
    }

    public function start(StockCount $c, int $userId): StockCount
    {
        return DB::transaction(function () use ($c, $userId) {
            $count = StockCount::query()->lockForUpdate()->findOrFail($c->id);

            if (! $count->status->canStart()) {
                throw BusinessRuleException::make("Cannot start count from status {$count->status->label()}.");
            }

            $this->createSnapshotLines($count);

            $count->forceFill([
                'status' => StockCountStatus::Counting,
                'started_at' => now(),
                'started_by' => $userId,
                'updated_by' => $userId,
            ])->save();

            return $count->fresh(['lines.item', 'warehouse']);
        });
    }

    public function snapshot(StockCount $c): StockCount
    {
        return DB::transaction(function () use ($c) {
            $count = StockCount::query()->lockForUpdate()->findOrFail($c->id);

            if ($count->status !== StockCountStatus::Counting) {
                throw BusinessRuleException::make('Stock can only be loaded while a count is in progress.');
            }

            if ($count->lines()->exists()) {
                throw BusinessRuleException::make('This count already has item lines and cannot be reloaded.');
            }

            $this->createSnapshotLines($count);

            return $count->fresh(['lines.item', 'warehouse']);
        });
    }

    public function recordCount(StockCount $c, array $counted, int $userId): StockCount
    {
        if ($c->status !== StockCountStatus::Counting) {
            throw BusinessRuleException::make('Counts can only be recorded while status is Counting.');
        }

        return DB::transaction(function () use ($c, $counted, $userId) {
            foreach ($c->lines as $line) {
                if (! array_key_exists($line->id, $counted)) {
                    continue;
                }

                $countedQty = (string) $counted[$line->id];
                $variance = bcsub($countedQty, (string) $line->system_quantity, 4);

                $line->forceFill([
                    'counted_quantity' => $countedQty,
                    'variance' => $variance,
                ])->save();
            }

            $c->forceFill(['updated_by' => $userId])->save();

            return $c->fresh(['lines.item']);
        });
    }

    public function submit(StockCount $c, int $userId): StockCount
    {
        if (! $c->status->canSubmit()) {
            throw BusinessRuleException::make("Cannot submit count from status {$c->status->label()}.");
        }

        if (! $c->lines()->exists()) {
            throw BusinessRuleException::make('Load stock items before submitting this count.');
        }

        // Ensure all lines are counted.
        $uncounted = $c->lines()->whereNull('counted_quantity')->count();
        if ($uncounted > 0) {
            throw BusinessRuleException::make("{$uncounted} line(s) have not been counted.");
        }

        $c->forceFill([
            'status' => StockCountStatus::Review,
            'submitted_at' => now(),
            'submitted_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $c->fresh();
    }

    public function approve(StockCount $c, int $userId): StockCount
    {
        if (! $c->status->canApprove()) {
            throw BusinessRuleException::make("Cannot approve count from status {$c->status->label()}.");
        }

        if ($c->submitted_by === $userId) {
            throw BusinessRuleException::make('You submitted this count, so you cannot approve it.');
        }

        $c->forceFill([
            'status' => StockCountStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $c->fresh();
    }

    /**
     * Post: create a stock adjustment from the variances and apply it.
     */
    public function post(StockCount $c, int $userId): StockCount
    {
        if (! $c->status->canPost()) {
            throw BusinessRuleException::make("Cannot post count from status {$c->status->label()}.");
        }

        return DB::transaction(function () use ($c, $userId) {
            $varianceLines = $c->lines()->where('variance', '!=', 0)->get();

            if ($varianceLines->isEmpty()) {
                // No variance — just mark as posted.
                $c->forceFill([
                    'status' => StockCountStatus::Posted,
                    'posted_at' => now(),
                    'posted_by' => $userId,
                    'updated_by' => $userId,
                ])->save();

                return $c->fresh();
            }

            // Create a stock adjustment with the variances.
            $adjustment = $this->adjustments->create(
                new StockAdjustmentData(
                    warehouseId: $c->warehouse_id,
                    adjustmentDate: now(),
                    reason: "Physical count {$c->number} variances",
                    lines: $varianceLines->map(fn ($l, $i) => new StockAdjustmentLineData(
                        itemId: $l->item_id,
                        quantity: (string) $l->variance,
                        unitCost: (string) $l->unit_cost,
                        notes: "System {$l->system_quantity} → Counted {$l->counted_quantity}",
                        position: $i,
                    )
                    )->all(),
                    notes: "Auto-generated from stock count {$c->number}",
                ),
                $userId,
            );

            $c->forceFill([
                'status' => StockCountStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $userId,
                'stock_adjustment_id' => $adjustment->id,
                'updated_by' => $userId,
            ])->save();

            return $c->fresh(['stockAdjustment']);
        });
    }

    public function cancel(StockCount $c, int $userId, ?string $reason = null): StockCount
    {
        if (! $c->status->canCancel()) {
            throw BusinessRuleException::make("Cannot cancel count from status {$c->status->label()}.");
        }

        $c->forceFill([
            'status' => StockCountStatus::Cancelled,
            'notes' => $reason ? trim(($c->notes ?? '')."\nCancelled: ".$reason) : $c->notes,
            'updated_by' => $userId,
        ])->save();

        return $c->fresh();
    }

    private function createSnapshotLines(StockCount $count): void
    {
        if ($count->lines()->exists()) {
            return;
        }

        $items = Item::query()
            ->active()
            ->stock()
            ->where('track_inventory', true)
            ->leftJoin('stock_balances', function ($join) use ($count): void {
                $join->on('stock_balances.item_id', '=', 'items.id')
                    ->where('stock_balances.warehouse_id', $count->warehouse_id);
            })
            ->select(['items.id', 'items.cost_price'])
            ->selectRaw('COALESCE(SUM(stock_balances.quantity), 0) as system_quantity')
            ->selectRaw('COALESCE(SUM(stock_balances.total_value), 0) as system_value')
            ->groupBy('items.id', 'items.cost_price')
            ->orderBy('items.code')
            ->get();

        foreach ($items as $position => $item) {
            $systemQuantity = (string) $item->system_quantity;
            $systemValue = (string) $item->system_value;
            $unitCost = bccomp($systemQuantity, '0', 4) === 0
                ? (string) $item->cost_price
                : bcdiv($systemValue, $systemQuantity, 4);

            $count->lines()->create([
                'item_id' => $item->id,
                'position' => $position,
                'system_quantity' => $systemQuantity,
                'counted_quantity' => null,
                'variance' => 0,
                'unit_cost' => $unitCost,
            ]);
        }
    }
}
