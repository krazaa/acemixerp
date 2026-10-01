<?php

declare(strict_types=1);

namespace Modules\Procurement\Services;

use App\Contracts\SequenceGenerator;
use App\Contracts\StockLedger;
use App\Data\StockMovementData;
use App\Enums\StockMovementType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\StockBatch;
use Modules\Procurement\Contracts\GoodsReceiptManager;
use Modules\Procurement\Contracts\PurchaseOrderManager;
use Modules\Procurement\Data\GoodsReceiptData;
use Modules\Procurement\Enums\GoodsReceiptStatus;
use Modules\Procurement\Events\GoodsReceived;
use Modules\Procurement\Exceptions\GoodsReceiptException;
use Modules\Procurement\Models\GoodsReceipt;
use Modules\Procurement\Models\GoodsReceiptLine;
use Modules\Procurement\Models\PurchaseOrder;

final class GoodsReceiptService implements GoodsReceiptManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly LineBrandService $brands,
        private readonly LineOriginService $origins,
        private readonly PurchaseOrderManager $purchaseOrders,
        private readonly StockLedger $stockLedger,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return GoodsReceipt::query()
            ->with(['purchaseOrder:id,number,vendor_id', 'vendor:id,code,name', 'warehouse:id,code,name'])
            ->withCount('lines')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['vendor_id'] ?? null, fn ($q, $v) => $q->where('vendor_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('received_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('received_date', '<=', $d))
            ->orderByDesc('received_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createDraft(GoodsReceiptData $data, int $userId): GoodsReceipt
    {
        if (count($data->lines) === 0) {
            throw GoodsReceiptException::noLines();
        }

        $po = PurchaseOrder::query()->findOrFail($data->purchaseOrderId);

        if (! $po->status->acceptsReceipt()) {
            throw GoodsReceiptException::poNotReceivable($po);
        }

        if (! $po->warehouse_id) {
            throw GoodsReceiptException::poMissingWarehouse($po);
        }

        return DB::transaction(function () use ($data, $po, $userId) {
            $grn = GoodsReceipt::query()->create([
                'number' => $this->sequences->next('goods_receipt', (int) $data->receivedDate->format('Y')),
                'purchase_order_id' => $po->id,
                'vendor_id' => $po->vendor_id,
                'warehouse_id' => $po->warehouse_id,
                'received_date' => $data->receivedDate,
                'supplier_delivery_note' => $data->supplierDeliveryNote,
                'carrier' => $data->carrier,
                'status' => GoodsReceiptStatus::Draft,
                'notes' => $data->notes,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data->lines as $line) {
                $grn->lines()->create(array_replace($line->toArray(), ['brand_id' => $this->brands->forOrderLine($line->brandId, $line->purchaseOrderLineId, $grn->purchase_order_id, $line->itemId, $line->position), 'origin_id' => $this->origins->forOrderLine($line->originId, $line->purchaseOrderLineId, $grn->purchase_order_id, $line->itemId, $line->position)]));
            }

            return $grn->fresh(['lines.item', 'purchaseOrder', 'vendor']);
        });
    }

    public function update(GoodsReceipt $grn, GoodsReceiptData $data, int $userId): GoodsReceipt
    {
        if (! $grn->status->isEditable()) {
            throw GoodsReceiptException::notEditable($grn);
        }

        return DB::transaction(function () use ($grn, $data, $userId) {
            $grn->lines()->delete();

            foreach ($data->lines as $line) {
                $grn->lines()->create(array_replace($line->toArray(), ['brand_id' => $this->brands->forOrderLine($line->brandId, $line->purchaseOrderLineId, $grn->purchase_order_id, $line->itemId, $line->position), 'origin_id' => $this->origins->forOrderLine($line->originId, $line->purchaseOrderLineId, $grn->purchase_order_id, $line->itemId, $line->position)]));
            }

            $grn->fill([
                'received_date' => $data->receivedDate,
                'supplier_delivery_note' => $data->supplierDeliveryNote,
                'carrier' => $data->carrier,
                'notes' => $data->notes,
                'updated_by' => $userId,
            ])->save();

            return $grn->fresh(['lines.item']);
        });
    }

    public function post(GoodsReceipt $grn, int $userId): GoodsReceipt
    {
        if ($grn->status !== GoodsReceiptStatus::Draft) {
            throw GoodsReceiptException::notPostable($grn);
        }

        if ($grn->lines()->count() === 0) {
            throw GoodsReceiptException::noLines();
        }

        // Validate every line: PO line belongs to this PO, over-receipt guard
        $po = $grn->purchaseOrder;
        foreach ($grn->lines as $line) {
            $poLine = $po->lines()->whereKey($line->purchase_order_line_id)->first();
            if (! $poLine) {
                throw GoodsReceiptException::lineMismatch($grn, $line);
            }

            $alreadyAccepted = $this->acceptedQuantityForLine($poLine->id);
            $proposedTotal = bcadd($alreadyAccepted, (string) $line->accepted_quantity, 4);

            if (bccomp($proposedTotal, (string) $poLine->quantity, 4) > 0) {
                throw GoodsReceiptException::overReceipt(
                    $poLine->item?->name ?? 'Item',
                    (string) $poLine->quantity,
                    $proposedTotal,
                );
            }
        }

        DB::transaction(function () use ($grn, $userId) {
            $movements = [];
            foreach ($grn->lines as $line) {
                if (bccomp((string) $line->accepted_quantity, '0', 4) <= 0) {
                    continue;
                }

                $batch = StockBatch::query()->updateOrCreate(
                    [
                        'item_id' => $line->item_id,
                        'warehouse_id' => $grn->warehouse_id,
                        'number' => $line->batch_number,
                    ],
                    array_filter([
                        'expiry_date' => $line->expiry_date,
                        'manufacturing_date' => $line->manufacturing_date,
                    ], static fn ($date): bool => $date !== null),
                );

                $unitCost = (string) $line->purchaseOrderLine()->value('unit_price');
                $movements[] = new StockMovementData(
                    itemId: $line->item_id,
                    warehouseId: $grn->warehouse_id,
                    quantity: (string) $line->accepted_quantity,
                    type: StockMovementType::Purchase,
                    occurredAt: $grn->received_date,
                    batchId: $batch->id,
                    reference: $grn->number,
                    notes: "Goods receipt {$grn->number}, batch {$batch->number}",
                    sourceType: GoodsReceiptLine::class,
                    sourceId: $line->id,
                    unitCost: $unitCost,
                );
            }

            $this->stockLedger->recordMany($movements);

            $grn->forceFill([
                'status' => GoodsReceiptStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $userId,
                'updated_by' => $userId,
            ])->save();

            // Recalculate PO line cumulative quantities + status
            $this->purchaseOrders->recalculateReceiptStatus($grn->purchaseOrder);
        });

        // Allows downstream integrations to react after the inventory ledger is updated.
        event(new GoodsReceived($grn->fresh()));

        return $grn->fresh(['lines.item', 'purchaseOrder']);
    }

    public function cancel(GoodsReceipt $grn, int $userId, ?string $reason = null): GoodsReceipt
    {
        if ($grn->status !== GoodsReceiptStatus::Draft) {
            throw GoodsReceiptException::notCancellable($grn);
        }

        $grn->forceFill([
            'status' => GoodsReceiptStatus::Cancelled,
            'notes' => $reason ? trim(($grn->notes ?? '')."\nCancelled: ".$reason) : $grn->notes,
            'updated_by' => $userId,
        ])->save();

        return $grn->fresh();
    }

    public function delete(GoodsReceipt $grn): void
    {
        if ($grn->status !== GoodsReceiptStatus::Draft) {
            throw GoodsReceiptException::cannotDelete($grn);
        }
        DB::transaction(fn () => $grn->delete());
    }

    public function acceptedQuantityForLine(int $purchaseOrderLineId): string
    {
        $sum = DB::table('goods_receipt_lines')
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
            ->where('goods_receipts.status', GoodsReceiptStatus::Posted->value)
            ->where('goods_receipt_lines.purchase_order_line_id', $purchaseOrderLineId)
            ->sum('goods_receipt_lines.accepted_quantity');

        return $sum ? (string) $sum : '0.0000';
    }
}
