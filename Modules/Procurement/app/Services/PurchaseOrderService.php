<?php

namespace Modules\Procurement\Services;

use App\Contracts\SequenceGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Procurement\Contracts\PurchaseOrderManager;
use Modules\Procurement\Data\PurchaseOrderData;
use Modules\Procurement\Data\PurchaseOrderLineData;
use Modules\Procurement\Enums\PurchaseOrderStatus;
use Modules\Procurement\Exceptions\PurchaseOrderException;
use Modules\Procurement\Models\PurchaseOrder;
use Modules\Procurement\Models\RequestForQuotation;

final class PurchaseOrderService implements PurchaseOrderManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly LineBrandService $brands,
        private readonly LineOriginService $origins,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->with(['vendor:id,code,name', 'department:id,name', 'creator:id,name', 'warehouse:id,name'])
            ->withCount(['lines', 'goodsReceipts'])
            ->when($filters['search'] ?? null, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('number', 'like', "%{$s}%")
                        ->orWhere('reference', 'like', "%{$s}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['vendor_id'] ?? null, fn ($q, $v) => $q->where('vendor_id', $v))
            ->when($filters['warehouse_id'] ?? null, fn ($q, $warehouse) => $q->where('warehouse_id', $warehouse))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('order_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('order_date', '<=', $d))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(PurchaseOrderData $data, int $userId): PurchaseOrder
    {
        if (count($data->lines) === 0) {
            throw PurchaseOrderException::noLines();
        }

        return DB::transaction(function () use ($data, $userId) {
            $po = PurchaseOrder::query()->create([
                'number' => $this->sequences->next(
                    'purchase_order',
                    (int) $data->orderDate->format('Y'),
                ),
                'vendor_id' => $data->vendorId,
                'order_date' => $data->orderDate,
                'expected_date' => $data->expectedDate,
                'department_id' => $data->departmentId,
                'cost_center_id' => $data->costCenterId,
                'warehouse_id' => $data->warehouseId,
                'currency_code' => strtoupper($data->currencyCode),
                'payment_term_id' => $data->paymentTermId,
                'reference' => $data->reference,
                'shipping_address' => $data->shippingAddress,
                'notes' => $data->notes,
                'terms' => $data->terms,
                'status' => PurchaseOrderStatus::Draft,
                'subtotal' => $data->subtotal(),
                'tax_total' => $data->taxTotal(),
                'total' => $data->total(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data->lines as $line) {
                $po->lines()->create(array_replace($line->toArray(), ['brand_id' => $this->brands->resolve($line->brandId, $line->position), 'origin_id' => $this->origins->resolve($line->originId, $line->position)]));
            }

            return $po->fresh(['lines.item', 'vendor']);
        });
    }

    public function createFromRfq(RequestForQuotation $rfq, int $userId): PurchaseOrder
    {
        if ($rfq->awarded_quotation_id === null) {
            throw new PurchaseOrderException('Use Create Purchase Orders for an RFQ awarded to multiple vendors.');
        }

        return $this->createOrdersFromRfq($rfq, $userId)->first();
    }

    /** @return Collection<int, PurchaseOrder> */
    public function createOrdersFromRfq(RequestForQuotation $rfq, int $userId): Collection
    {
        return DB::transaction(function () use ($rfq, $userId): Collection {
            $rfq = RequestForQuotation::query()->lockForUpdate()->findOrFail($rfq->id);
            if ($rfq->status->value !== 'awarded') {
                throw PurchaseOrderException::rfqNotAwarded($rfq);
            }
            if ($rfq->converted_to_id !== null || $rfq->purchaseOrders()->withTrashed()->exists()) {
                throw new PurchaseOrderException('Purchase orders have already been created for this RFQ.');
            }
            $rfq->load(['lines.awardedQuotationLine.quotation', 'awardedQuotation.lines.rfqLine']);
            $selected = $rfq->lines->pluck('awardedQuotationLine')->filter();
            if ($selected->isEmpty() && $rfq->awardedQuotation) {
                $selected = $rfq->awardedQuotation->lines;
            } elseif ($selected->count() !== $rfq->lines->count()) {
                throw new PurchaseOrderException('Every RFQ item must be awarded before creating purchase orders.');
            }
            if ($selected->isEmpty()) {
                throw PurchaseOrderException::noLines();
            }
            $rfqLines = $rfq->lines->keyBy('id');
            $orders = collect();
            $warehouseIds = $rfq->purchaseRequisitions()->pluck('warehouse_id')->unique();
            $warehouseId = $warehouseIds->count() === 1 ? $warehouseIds->first() : null;
            foreach ($selected->groupBy('vendor_quotation_id') as $quoteLines) {
                $quotation = $quoteLines->first()->quotation;
                $lines = $quoteLines->values()->map(function ($quoteLine, int $position) use ($rfqLines): PurchaseOrderLineData {
                    $line = $rfqLines->get($quoteLine->rfq_line_id);
                    if (! $line) {
                        throw new PurchaseOrderException('An awarded quotation line does not belong to this RFQ.');
                    }

                    return new PurchaseOrderLineData(
                        itemId: $line->item_id,
                        brandId: $line->brand_id,
                        originId: $line->origin_id,
                        unitId: $line->unit_id,
                        quantity: (string) $quoteLine->quantity,
                        unitPrice: (string) $quoteLine->unit_price,
                        taxRate: (string) $quoteLine->tax_rate,
                        whtTaxRate: (string) ($quoteLine->wht_tax_rate ?? 0),
                        specification: $line->specification,
                        rfqLineId: $line->id,
                        position: $position,
                    );
                })->all();
                $leadTime = $quoteLines->max('lead_time_days') ?? $quotation->lead_time_days ?? 7;
                $po = $this->create(new PurchaseOrderData(
                    vendorId: $quotation->vendor_id,
                    orderDate: now(),
                    currencyCode: $quotation->currency_code,
                    lines: $lines,
                    expectedDate: now()->addDays((int) $leadTime),
                    departmentId: $rfq->department_id,
                    costCenterId: $rfq->cost_center_id,
                    warehouseId: $warehouseId,
                    reference: "From RFQ {$rfq->number}",
                    notes: "Created from awarded items in RFQ {$rfq->number}",
                    terms: $rfq->terms,
                ), $userId);
                $po->forceFill(['source_type' => RequestForQuotation::class, 'source_id' => $rfq->id])->save();
                $orders->push($po);
            }
            $rfq->forceFill([
                'converted_to_type' => PurchaseOrder::class,
                'converted_to_id' => $orders->first()->id,
                'updated_by' => $userId,
            ])->save();

            return $orders;
        });
    }

    public function update(PurchaseOrder $po, PurchaseOrderData $data, int $userId): PurchaseOrder
    {
        if (! $po->status->isEditable()) {
            throw PurchaseOrderException::notEditable($po);
        }

        if (count($data->lines) === 0) {
            throw PurchaseOrderException::noLines();
        }

        return DB::transaction(function () use ($po, $data, $userId) {
            $po->lines()->delete();

            foreach ($data->lines as $line) {
                $po->lines()->create(array_replace($line->toArray(), ['brand_id' => $this->brands->resolve($line->brandId, $line->position), 'origin_id' => $this->origins->resolve($line->originId, $line->position)]));
            }

            $po->fill([
                'vendor_id' => $data->vendorId,
                'order_date' => $data->orderDate,
                'expected_date' => $data->expectedDate,
                'department_id' => $data->departmentId,
                'cost_center_id' => $data->costCenterId,
                'warehouse_id' => $data->warehouseId,
                'currency_code' => strtoupper($data->currencyCode),
                'payment_term_id' => $data->paymentTermId,
                'reference' => $data->reference,
                'shipping_address' => $data->shippingAddress,
                'notes' => $data->notes,
                'terms' => $data->terms,
                'subtotal' => $data->subtotal(),
                'tax_total' => $data->taxTotal(),
                'total' => $data->total(),
                'updated_by' => $userId,
            ])->save();

            return $po->fresh(['lines.item', 'vendor']);
        });
    }

    public function submit(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        if (! $po->status->canSubmit()) {
            throw PurchaseOrderException::invalidTransition($po, 'submit');
        }

        if ($po->lines()->count() === 0) {
            throw PurchaseOrderException::noLines();
        }

        $po->forceFill([
            'status' => PurchaseOrderStatus::Submitted,
            'submitted_at' => now(),
            'submitted_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $po->fresh();
    }

    public function approve(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        if (! $po->status->canApprove()) {
            throw PurchaseOrderException::invalidTransition($po, 'approve');
        }

        // Segregation of duties: the submitter cannot approve their own PO.
        if ($po->submitted_by === $userId) {
            throw PurchaseOrderException::cannotSelfApprove($po);
        }

        $po->forceFill([
            'status' => PurchaseOrderStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $po->fresh();
    }

    public function reject(PurchaseOrder $po, int $userId, string $reason): PurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::Submitted) {
            throw PurchaseOrderException::invalidTransition($po, 'reject');
        }

        $po->forceFill([
            'status' => PurchaseOrderStatus::Rejected,
            'notes' => trim(($po->notes ?? '')."\nRejected: ".$reason),
            'updated_by' => $userId,
        ])->save();

        return $po->fresh();
    }

    public function issue(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        if (! $po->status->canIssue()) {
            throw PurchaseOrderException::invalidTransition($po, 'issue');
        }

        $po->forceFill([
            'status' => PurchaseOrderStatus::Issued,
            'issued_at' => now(),
            'issued_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $po->fresh();
    }

    public function cancel(PurchaseOrder $po, int $userId, ?string $reason = null): PurchaseOrder
    {
        if (! $po->status->canCancel()) {
            throw PurchaseOrderException::invalidTransition($po, 'cancel');
        }

        $po->forceFill([
            'status' => PurchaseOrderStatus::Cancelled,
            'notes' => $reason
                ? trim(($po->notes ?? '')."\nCancelled: ".$reason)
                : $po->notes,
            'updated_by' => $userId,
        ])->save();

        return $po->fresh();
    }

    public function close(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        if (! in_array($po->status, [
            PurchaseOrderStatus::Received,
            PurchaseOrderStatus::PartiallyReceived,
        ], true)) {
            throw PurchaseOrderException::invalidTransition($po, 'close');
        }

        $po->forceFill([
            'status' => PurchaseOrderStatus::Closed,
            'closed_at' => now(),
            'updated_by' => $userId,
        ])->save();

        return $po->fresh();
    }

    public function delete(PurchaseOrder $po): void
    {
        if ($po->status !== PurchaseOrderStatus::Draft) {
            throw PurchaseOrderException::cannotDelete($po);
        }

        DB::transaction(fn () => $po->delete());
    }

    /**
     * Recompute received_quantity / rejected_quantity on every line from posted
     * GRNs, then update the PO's status accordingly.
     *
     * Called by GoodsReceiptService::post() inside its own transaction.
     */
    public function recalculateReceiptStatus(PurchaseOrder $po): void
    {
        DB::transaction(function () use ($po) {
            /** @var PurchaseOrder $locked */
            $locked = PurchaseOrder::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($po->id);

            foreach ($locked->lines as $line) {
                $accepted = (string) DB::table('goods_receipt_lines')
                    ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
                    ->where('goods_receipts.status', 'posted')
                    ->where('goods_receipt_lines.purchase_order_line_id', $line->id)
                    ->sum('goods_receipt_lines.accepted_quantity');

                $rejected = (string) DB::table('goods_receipt_lines')
                    ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
                    ->where('goods_receipts.status', 'posted')
                    ->where('goods_receipt_lines.purchase_order_line_id', $line->id)
                    ->sum('goods_receipt_lines.rejected_quantity');

                $line->forceFill([
                    'received_quantity' => $accepted !== '' ? $accepted : '0.0000',
                    'rejected_quantity' => $rejected !== '' ? $rejected : '0.0000',
                ])->save();
            }

            $locked->refresh()->load('lines');

            if (! $locked->status->acceptsReceipt()) {
                return;
            }

            if ($locked->isFullyReceived()) {
                $locked->forceFill(['status' => PurchaseOrderStatus::Received])->save();
            } elseif ($locked->isPartiallyReceived()) {
                $locked->forceFill(['status' => PurchaseOrderStatus::PartiallyReceived])->save();
            }
        });
    }
}
