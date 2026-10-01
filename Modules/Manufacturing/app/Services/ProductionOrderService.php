<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Services;

use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Contracts\StockLedger;
use App\Contracts\SystemAccountManager;
use App\Data\JournalEntryData;
use App\Data\JournalLineData;
use App\Data\StockMovementData;
use App\Enums\StockMovementType;
use App\Enums\SystemAccountRole;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\StockBalance;
use Modules\Manufacturing\Contracts\ProductionOrderManager;
use Modules\Manufacturing\Data\ProductionOrderData;
use Modules\Manufacturing\Enums\ProductionOrderStatus;
use Modules\Manufacturing\Exceptions\ManufacturingException;
use Modules\Manufacturing\Models\BomLine;
use Modules\Manufacturing\Models\ProductionOrder;
use Modules\Manufacturing\Models\ProductionOutput;

final class ProductionOrderService implements ProductionOrderManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly StockLedger $ledger,
        private readonly JournalPoster $poster,
        private readonly SystemAccountManager $systemAccounts,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return ProductionOrder::query()
            ->with(['product:id,code,name', 'bom:id,code', 'creator:id,name'])
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('scheduled_start_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('scheduled_start_date', '<=', $d))
            ->orderByDesc('scheduled_start_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(ProductionOrderData $data, int $userId): ProductionOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = ProductionOrder::query()->create([
                'number' => $this->sequences->next('production_order', (int) now()->format('Y')),
                'bill_of_materials_id' => $data->bomId,
                'product_id' => $data->productId,
                'planned_quantity' => $data->plannedQuantity,
                'source_warehouse_id' => $data->sourceWarehouseId,
                'scheduled_start_date' => $data->scheduledStartDate,
                'scheduled_end_date' => $data->scheduledEndDate,
                'sales_order_id' => $data->salesOrderId,
                'type' => $data->type,
                'status' => ProductionOrderStatus::Draft,
                'notes' => $data->notes,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Explode the BOM into required component lines.
            $bom = $order->bom()->with('lines.component')->firstOrFail();
            $multiplier = bcdiv((string) $order->planned_quantity, (string) $bom->output_quantity, 8);

            foreach ($bom->lines as $i => $bomLine) {
                $required = bcmul((string) $bomLine->effectiveQuantity(), $multiplier, 4);
                $unitCost = (string) ($bomLine->component?->cost_price ?? '0.0000');

                $order->lines()->create([
                    'component_id' => $bomLine->component_id,
                    'batch_id' => $bomLine->batch_id,
                    'unit_id' => $bomLine->unit_id,
                    'required_quantity' => $required,
                    'waste_quantity' => $this->wasteQuantity($bomLine, $multiplier),
                    'unit_cost' => $unitCost,
                    'line_cost' => bcmul($required, $unitCost, 4),
                    'position' => $i,
                ]);
            }

            return $order->fresh(['lines.component', 'bom', 'product']);
        });
    }

    public function plan(ProductionOrder $order, int $userId): ProductionOrder
    {
        if (! $order->status->canPlan()) {
            throw ManufacturingException::invalidTransition($order, 'plan');
        }

        $order->forceFill([
            'status' => ProductionOrderStatus::Planned,
            'planned_at' => now(),
            'planned_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $order->fresh();
    }

    public function release(ProductionOrder $order, int $userId): ProductionOrder
    {
        if (! $order->status->canRelease()) {
            throw ManufacturingException::invalidTransition($order, 'release');
        }

        // Verify component stock is available.
        $order->load('lines.component');
        foreach ($order->lines as $line) {
            $onHand = $this->ledger->onHand($line->component_id, $order->source_warehouse_id);
            if (bccomp($onHand, (string) $line->required_quantity, 4) < 0) {
                throw ManufacturingException::insufficientComponentStock(
                    $line->component?->name ?? 'Component',
                    $onHand,
                    (string) $line->required_quantity,
                );
            }
        }

        $order->forceFill([
            'status' => ProductionOrderStatus::Released,
            'released_at' => now(),
            'released_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $order->fresh();
    }

    public function start(ProductionOrder $order, int $userId): ProductionOrder
    {
        if (! $order->status->canStart()) {
            throw ManufacturingException::invalidTransition($order, 'start');
        }

        return DB::transaction(function () use ($order, $userId) {
            /** @var ProductionOrder $locked */
            $locked = ProductionOrder::query()->lockForUpdate()->findOrFail($order->id);
            $locked->load(['lines', 'bom.lines']);

            $multiplier = bcdiv(
                (string) $locked->planned_quantity,
                (string) $locked->bom->output_quantity,
                8,
            );
            $wasteByPosition = $locked->bom->lines
                ->mapWithKeys(fn (BomLine $bomLine): array => [
                    $bomLine->position => $this->wasteQuantity($bomLine, $multiplier),
                ]);

            // Issue components from stock — this is where raw material leaves.
            $movements = [];
            foreach ($locked->lines as $line) {
                $wasteQuantity = $wasteByPosition->get($line->position, '0.0000');

                if (bccomp((string) $line->issued_quantity, '0', 4) > 0) {
                    $line->forceFill(['waste_quantity' => $wasteQuantity])->save();

                    continue;
                }

                $batchId = $line->batch_id ?: $this->availableBatchId($line->component_id, $locked->source_warehouse_id);

                if ($batchId !== $line->batch_id) {
                    $line->forceFill(['batch_id' => $batchId])->save();
                }

                $movements[] = new StockMovementData(
                    itemId: $line->component_id,
                    warehouseId: $locked->source_warehouse_id,
                    quantity: bcmul((string) $line->required_quantity, '-1', 4),
                    type: StockMovementType::Consumption,
                    occurredAt: now(),
                    batchId: $batchId,
                    reference: $locked->number,
                    notes: "Issued to production order {$locked->number}",
                    sourceType: ProductionOrder::class,
                    sourceId: $locked->id,
                    unitCost: (string) $line->unit_cost,
                );

                $line->forceFill([
                    'issued_quantity' => $line->required_quantity,
                    'waste_quantity' => $wasteQuantity,
                    'line_cost' => bcmul((string) $line->required_quantity, (string) $line->unit_cost, 4),
                ])->save();
            }

            if (! empty($movements)) {
                $this->ledger->recordMany($movements);
            }

            $locked->forceFill([
                'status' => ProductionOrderStatus::InProgress,
                'actual_start_date' => now()->toDateString(),
                'started_at' => now(),
                'started_by' => $userId,
                'updated_by' => $userId,
            ])->save();

            return $locked->fresh();
        });
    }

    /** Record a partial or final production output, its inventory, and its cost. */
    public function complete(ProductionOrder $order, string $producedQuantity, int $userId): ProductionOrder
    {
        if (! $order->status->canComplete()) {
            throw ManufacturingException::invalidTransition($order, 'complete');
        }

        if (bccomp($producedQuantity, '0', 4) <= 0) {
            throw ManufacturingException::invalidQuantity($producedQuantity);
        }

        return DB::transaction(function () use ($order, $producedQuantity, $userId) {
            /** @var ProductionOrder $locked */
            $locked = ProductionOrder::query()->lockForUpdate()->findOrFail($order->id);

            if (! $locked->status->canComplete()) {
                throw ManufacturingException::invalidTransition($locked, 'complete');
            }

            $remainingQuantity = $locked->remainingQuantity();
            if (bccomp($producedQuantity, $remainingQuantity, 4) > 0) {
                throw ManufacturingException::exceedsRemainingQuantity($remainingQuantity);
            }

            $materialsTotal = '0.0000';
            foreach ($locked->lines as $line) {
                $materialsTotal = bcadd($materialsTotal, (string) $line->line_cost, 4);
            }

            $plannedQuantity = (string) $locked->planned_quantity;
            $materialsCost = bcmul(bcdiv($materialsTotal, $plannedQuantity, 8), $producedQuantity, 4);
            $labour = bcmul(bcdiv((string) $locked->bom->labour_cost, $plannedQuantity, 8), $producedQuantity, 4);
            $overhead = bcmul(bcdiv((string) $locked->bom->overhead_cost, $plannedQuantity, 8), $producedQuantity, 4);
            $totalCost = bcadd(bcadd($materialsCost, $labour, 4), $overhead, 4);
            $unitCost = bcdiv($totalCost, $producedQuantity, 4);

            $output = $locked->outputs()->create([
                'product_id' => $locked->product_id,
                'quantity' => $producedQuantity,
                'unit_cost' => $unitCost,
                'line_cost' => $totalCost,
                'produced_at' => now(),
                'produced_by' => $userId,
            ]);

            // Produce finished goods in the same warehouse that issued components.
            $this->ledger->record(new StockMovementData(
                itemId: $locked->product_id,
                warehouseId: $locked->source_warehouse_id,
                quantity: $producedQuantity,
                type: StockMovementType::Production,
                occurredAt: now(),
                reference: $locked->number,
                notes: "Produced from {$locked->number}",
                sourceType: ProductionOutput::class,
                sourceId: $output->id,
                unitCost: $unitCost,
            ));

            // GL posting: debit finished goods inventory, credit WIP / raw materials.
            $invAcct = $this->systemAccounts->resolve(SystemAccountRole::Inventory);
            if (! $invAcct) {
                throw ManufacturingException::missingSystemAccount('inventory');
            }

            // For simplicity we debit Inventory and credit Inventory (net zero on
            // quantity change, but cost is reclassified). In a full WIP setup you
            // would debit FG and credit WIP, and WIP would have been debited when
            // raw materials were consumed.
            $journal = $this->poster->post(new JournalEntryData(
                entryDate: now(),
                description: "Production output {$locked->number} — {$locked->product->name}",
                lines: [
                    new JournalLineData(
                        accountId: $invAcct->id,
                        debit: $totalCost,
                        credit: '0.0000',
                        memo: "FG inventory — {$locked->number}",
                    ),
                    new JournalLineData(
                        accountId: $invAcct->id,
                        debit: '0.0000',
                        credit: $totalCost,
                        memo: "Cost reclass — {$locked->number}",
                    ),
                ],
            ), [
                'source_type' => ProductionOutput::class,
                'source_id' => $output->id,
                'user_id' => $userId,
            ]);

            $newProducedQuantity = bcadd((string) $locked->produced_quantity, $producedQuantity, 4);
            $isComplete = bccomp($newProducedQuantity, $plannedQuantity, 4) === 0;

            $locked->forceFill([
                'status' => $isComplete ? ProductionOrderStatus::Completed : ProductionOrderStatus::InProgress,
                'produced_quantity' => $newProducedQuantity,
                'actual_end_date' => $isComplete ? now()->toDateString() : null,
                'completed_at' => $isComplete ? now() : null,
                'completed_by' => $isComplete ? $userId : null,
                'materials_cost' => bcadd((string) $locked->materials_cost, $materialsCost, 4),
                'labour_cost' => bcadd((string) $locked->labour_cost, $labour, 4),
                'overhead_cost' => bcadd((string) $locked->overhead_cost, $overhead, 4),
                'total_cost' => bcadd((string) $locked->total_cost, $totalCost, 4),
                'unit_cost' => bcdiv(
                    bcadd((string) $locked->total_cost, $totalCost, 4),
                    $newProducedQuantity,
                    4,
                ),
                'journal_entry_id' => $journal->id,
                'updated_by' => $userId,
            ])->save();

            return $locked->fresh(['outputs', 'journalEntry']);
        });
    }

    public function close(ProductionOrder $order, int $userId): ProductionOrder
    {
        if (! $order->status->canClose()) {
            throw ManufacturingException::invalidTransition($order, 'close');
        }

        $order->forceFill([
            'status' => ProductionOrderStatus::Closed,
            'closed_at' => now(),
            'closed_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $order->fresh();
    }

    public function cancel(ProductionOrder $order, int $userId, ?string $reason = null): ProductionOrder
    {
        if (! $order->status->canCancel()) {
            throw ManufacturingException::invalidTransition($order, 'cancel');
        }

        return DB::transaction(function () use ($order, $userId, $reason) {
            // If components were issued, return them to stock.
            if (bccomp((string) $order->produced_quantity, '0', 4) === 0
                && $order->lines()->where('issued_quantity', '>', 0)->exists()) {
                $movements = [];
                foreach ($order->lines as $line) {
                    if (bccomp((string) $line->issued_quantity, '0', 4) <= 0) {
                        continue;
                    }

                    $movements[] = new StockMovementData(
                        itemId: $line->component_id,
                        warehouseId: $order->source_warehouse_id,
                        quantity: (string) $line->issued_quantity,
                        type: StockMovementType::Return,
                        occurredAt: now(),
                        batchId: $line->batch_id,
                        reference: $order->number,
                        notes: "Cancelled production order {$order->number} — returned to stock",
                        sourceType: ProductionOrder::class,
                        sourceId: $order->id,
                        unitCost: (string) $line->unit_cost,
                    );

                    $line->forceFill(['issued_quantity' => '0.0000'])->save();
                }

                if (! empty($movements)) {
                    $this->ledger->recordMany($movements);
                }
            }

            $order->forceFill([
                'status' => ProductionOrderStatus::Cancelled,
                'notes' => $reason
                    ? trim(($order->notes ?? '')."\nCancelled: ".$reason)
                    : $order->notes,
                'updated_by' => $userId,
            ])->save();

            return $order->fresh();
        });
    }

    public function update(
        ProductionOrder $order,
        ProductionOrderData $data,
        int $userId,
    ): ProductionOrder {
        if (! $order->status->isEditable()) {
            throw ManufacturingException::invalidTransition($order, 'edit');
        }

        return DB::transaction(function () use ($order, $data, $userId) {
            $order->forceFill([
                'source_warehouse_id' => $data->sourceWarehouseId,
                'planned_quantity' => $data->plannedQuantity,
                'scheduled_start_date' => $data->scheduledStartDate,
                'scheduled_end_date' => $data->scheduledEndDate,
                'sales_order_id' => $data->salesOrderId,
                'type' => $data->type,
                'notes' => $data->notes,
                'updated_by' => $userId,
            ])->save();

            // If the planned quantity changed, recompute the required component
            // quantities from the BOM. Only allowed before release.
            if (in_array($order->status, [ProductionOrderStatus::Draft, ProductionOrderStatus::Planned], true)
                && bccomp((string) $order->getOriginal('planned_quantity'), (string) $order->planned_quantity, 4) !== 0) {
                $order->lines()->delete();

                $bom = $order->bom()->with('lines.component')->firstOrFail();
                $multiplier = bcdiv((string) $order->planned_quantity, (string) $bom->output_quantity, 8);

                foreach ($bom->lines as $i => $bomLine) {
                    $required = bcmul((string) $bomLine->effectiveQuantity(), $multiplier, 4);
                    $unitCost = (string) ($bomLine->component?->cost_price ?? '0.0000');

                    $order->lines()->create([
                        'component_id' => $bomLine->component_id,
                        'batch_id' => $bomLine->batch_id,
                        'unit_id' => $bomLine->unit_id,
                        'required_quantity' => $required,
                        'waste_quantity' => $this->wasteQuantity($bomLine, $multiplier),
                        'unit_cost' => $unitCost,
                        'line_cost' => bcmul($required, $unitCost, 4),
                        'position' => $i,
                    ]);
                }
            }

            return $order->fresh(['lines.component', 'bom']);
        });
    }

    private function availableBatchId(int $itemId, int $warehouseId): ?int
    {
        return StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->whereNotNull('batch_id')
            ->where('quantity', '>', 0)
            ->orderBy('batch_id')
            ->value('batch_id');
    }

    private function wasteQuantity(BomLine $bomLine, string $multiplier): string
    {
        $baseQuantity = bcmul((string) $bomLine->quantity, $multiplier, 4);
        $requiredQuantity = bcmul((string) $bomLine->effectiveQuantity(), $multiplier, 4);
        $wasteQuantity = bcsub($requiredQuantity, $baseQuantity, 4);

        return bccomp($wasteQuantity, '0', 4) > 0 ? $wasteQuantity : '0.0000';
    }
}
