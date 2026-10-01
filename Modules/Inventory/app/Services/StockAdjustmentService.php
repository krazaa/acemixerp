<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Contracts\SequenceGenerator;
use App\Contracts\StockLedger;
use App\Data\StockMovementData;
use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Contracts\StockAdjustmentManager;
use Modules\Inventory\Data\StockAdjustmentData;
use Modules\Inventory\Enums\AdjustmentStatus;
use Modules\Inventory\Models\StockAdjustment;

final class StockAdjustmentService implements StockAdjustmentManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly StockLedger $ledger,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    /** @return Collection<int, StockAdjustment> */
    public function all(array $filters = []): Collection
    {
        return $this->filteredQuery($filters)->get();
    }

    /** @return Builder<StockAdjustment> */
    private function filteredQuery(array $filters): Builder
    {
        return StockAdjustment::query()
            ->with(['warehouse:id,code,name', 'creator:id,name'])
            ->withCount('lines')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('number', 'like', "%{$s}%"))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['warehouse_id'] ?? null, fn ($q, $w) => $q->where('warehouse_id', $w))
            ->orderByDesc('adjustment_date')
            ->orderByDesc('id');
    }

    public function create(StockAdjustmentData $data, int $userId): StockAdjustment
    {
        if (count($data->lines) === 0) {
            throw BusinessRuleException::make('A stock adjustment must have at least one line.');
        }

        return DB::transaction(function () use ($data, $userId) {
            $adjustment = StockAdjustment::query()->create([
                'number' => $this->sequences->next('stock_adjustment', (int) $data->adjustmentDate->format('Y')),
                'warehouse_id' => $data->warehouseId,
                'adjustment_date' => $data->adjustmentDate,
                'reason' => $data->reason,
                'notes' => $data->notes,
                'status' => AdjustmentStatus::Draft,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data->lines as $line) {
                $adjustment->lines()->create($line->toArray());
            }

            return $adjustment->fresh(['lines.item', 'warehouse']);
        });
    }

    public function submit(StockAdjustment $a, int $userId): StockAdjustment
    {
        if (! $a->status->canSubmit()) {
            throw BusinessRuleException::make("Cannot submit adjustment from status {$a->status->label()}.");
        }

        $a->forceFill([
            'status' => AdjustmentStatus::Submitted,
            'submitted_at' => now(),
            'submitted_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $a->fresh();
    }

    public function approve(StockAdjustment $a, int $userId): StockAdjustment
    {
        if (! $a->status->canApprove()) {
            throw BusinessRuleException::make("Cannot approve adjustment from status {$a->status->label()}.");
        }

        if ($a->submitted_by === $userId) {
            throw BusinessRuleException::make('You submitted this adjustment, so you cannot approve it.');
        }

        $a->forceFill([
            'status' => AdjustmentStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $a->fresh();
    }

    public function post(StockAdjustment $a, int $userId): StockAdjustment
    {
        if (! $a->status->canPost()) {
            throw BusinessRuleException::make("Cannot post adjustment from status {$a->status->label()}.");
        }

        return DB::transaction(function () use ($a, $userId) {
            $movements = [];
            foreach ($a->lines as $line) {
                $movements[] = new StockMovementData(
                    itemId: $line->item_id,
                    warehouseId: $a->warehouse_id,
                    quantity: (string) $line->quantity,        // signed
                    type: StockMovementType::Adjustment,
                    occurredAt: now(),
                    reference: $a->number,
                    notes: "Adjustment: {$a->reason}",
                    sourceType: StockAdjustment::class,
                    sourceId: $a->id,
                    unitCost: (string) $line->unit_cost,
                );
            }

            $this->ledger->recordMany($movements);

            $a->forceFill([
                'status' => AdjustmentStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $userId,
                'updated_by' => $userId,
            ])->save();

            return $a->fresh();
        });
    }

    public function cancel(StockAdjustment $a, int $userId, ?string $reason = null): StockAdjustment
    {
        if (! $a->status->canCancel()) {
            throw BusinessRuleException::make("Cannot cancel adjustment from status {$a->status->label()}.");
        }

        $a->forceFill([
            'status' => AdjustmentStatus::Cancelled,
            'notes' => $reason ? trim(($a->notes ?? '')."\nCancelled: ".$reason) : $a->notes,
            'updated_by' => $userId,
        ])->save();

        return $a->fresh();
    }
}
