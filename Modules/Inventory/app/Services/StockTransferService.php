<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Contracts\SequenceGenerator;
use App\Contracts\StockLedger;
use App\Data\StockMovementData;
use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Contracts\StockTransferManager;
use Modules\Inventory\Data\StockTransferData;
use Modules\Inventory\Enums\TransferStatus;
use Modules\Inventory\Models\StockTransfer;

final class StockTransferService implements StockTransferManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly StockLedger $ledger,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return StockTransfer::query()
            ->with(['fromWarehouse:id,code,name', 'toWarehouse:id,code,name', 'creator:id,name'])
            ->withCount('lines')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('number', 'like', "%{$s}%"))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['from_warehouse_id'] ?? null, fn ($q, $w) => $q->where('from_warehouse_id', $w))
            ->when($filters['to_warehouse_id'] ?? null, fn ($q, $w) => $q->where('to_warehouse_id', $w))
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(StockTransferData $data, int $userId): StockTransfer
    {
        if ($data->fromWarehouseId === $data->toWarehouseId) {
            throw BusinessRuleException::make('Source and destination warehouses must differ.');
        }
        if (count($data->lines) === 0) {
            throw BusinessRuleException::make('A stock transfer must have at least one line.');
        }

        return DB::transaction(function () use ($data, $userId) {
            $transfer = StockTransfer::query()->create([
                'number' => $this->sequences->next('stock_transfer', (int) $data->transferDate->format('Y')),
                'from_warehouse_id' => $data->fromWarehouseId,
                'to_warehouse_id' => $data->toWarehouseId,
                'transfer_date' => $data->transferDate,
                'expected_arrival_date' => $data->expectedArrivalDate,
                'notes' => $data->notes,
                'status' => TransferStatus::Draft,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data->lines as $line) {
                $transfer->lines()->create($line->toArray());
            }

            return $transfer->fresh(['lines.item', 'fromWarehouse', 'toWarehouse']);
        });
    }

    public function submit(StockTransfer $t, int $userId): StockTransfer
    {
        if (! $t->status->canSubmit()) {
            throw BusinessRuleException::make("Cannot submit transfer from status {$t->status->label()}.");
        }

        $t->forceFill([
            'status' => TransferStatus::Submitted,
            'submitted_at' => now(),
            'submitted_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $t->fresh();
    }

    public function approve(StockTransfer $t, int $userId): StockTransfer
    {
        if (! $t->status->canApprove()) {
            throw BusinessRuleException::make("Cannot approve transfer from status {$t->status->label()}.");
        }

        if ($t->submitted_by === $userId) {
            throw BusinessRuleException::make('You submitted this transfer, so you cannot approve it.');
        }

        $t->forceFill([
            'status' => TransferStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $t->fresh();
    }

    /**
     * Dispatch: record outbound movements from the source warehouse.
     */
    public function dispatch(StockTransfer $t, int $userId): StockTransfer
    {
        if (! $t->status->canDispatch()) {
            throw BusinessRuleException::make("Cannot dispatch transfer from status {$t->status->label()}.");
        }

        return DB::transaction(function () use ($t, $userId) {
            $movements = [];
            foreach ($t->lines as $line) {
                $movements[] = new StockMovementData(
                    itemId: $line->item_id,
                    warehouseId: $t->from_warehouse_id,
                    quantity: bcmul((string) $line->quantity, '-1', 4),  // outbound
                    type: StockMovementType::TransferOut,
                    occurredAt: now(),
                    reference: $t->number,
                    notes: "Transfer {$t->number} to {$t->toWarehouse->name}",
                    sourceType: StockTransfer::class,
                    sourceId: $t->id,
                );

                $line->forceFill(['dispatched_quantity' => $line->quantity])->save();
            }

            $this->ledger->recordMany($movements);

            $t->forceFill([
                'status' => TransferStatus::InTransit,
                'dispatched_at' => now(),
                'dispatched_by' => $userId,
                'updated_by' => $userId,
            ])->save();

            return $t->fresh();
        });
    }

    /**
     * Receive: record inbound movements into the destination warehouse.
     */
    public function receive(StockTransfer $t, array $receivedQuantities, int $userId): StockTransfer
    {
        if (! $t->status->canReceive()) {
            throw BusinessRuleException::make("Cannot receive transfer from status {$t->status->label()}.");
        }

        return DB::transaction(function () use ($t, $receivedQuantities, $userId) {
            $movements = [];
            foreach ($t->lines as $line) {
                $received = (string) ($receivedQuantities[$line->id] ?? $line->quantity);

                if (bccomp($received, '0', 4) <= 0) {
                    continue;
                }
                if (bccomp($received, (string) $line->quantity, 4) > 0) {
                    throw BusinessRuleException::make(
                        "Received quantity cannot exceed dispatched quantity for line {$line->id}."
                    );
                }

                $movements[] = new StockMovementData(
                    itemId: $line->item_id,
                    warehouseId: $t->to_warehouse_id,
                    quantity: $received,                       // inbound
                    type: StockMovementType::TransferIn,
                    occurredAt: now(),
                    reference: $t->number,
                    notes: "Transfer {$t->number} from {$t->fromWarehouse->name}",
                    sourceType: StockTransfer::class,
                    sourceId: $t->id,
                    unitCost: (string) $line->unit_cost,
                );

                $line->forceFill(['received_quantity' => $received])->save();
            }

            $this->ledger->recordMany($movements);

            $t->forceFill([
                'status' => TransferStatus::Received,
                'received_at' => now(),
                'received_by' => $userId,
                'updated_by' => $userId,
            ])->save();

            return $t->fresh();
        });
    }

    public function cancel(StockTransfer $t, int $userId, ?string $reason = null): StockTransfer
    {
        if (! $t->status->canCancel()) {
            throw BusinessRuleException::make("Cannot cancel transfer from status {$t->status->label()}.");
        }

        $t->forceFill([
            'status' => TransferStatus::Cancelled,
            'notes' => $reason ? trim(($t->notes ?? '')."\nCancelled: ".$reason) : $t->notes,
            'updated_by' => $userId,
        ])->save();

        return $t->fresh();
    }
}
