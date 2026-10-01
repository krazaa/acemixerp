<?php

namespace Modules\Procurement\Services;

use App\Contracts\SequenceGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
    public function __construct(private readonly SequenceGenerator $sequences) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->with(['vendor:id,code,name', 'department:id,name', 'creator:id,name'])
            ->withCount(['lines', 'goodsReceipts'])
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['vendor_id'] ?? null, fn ($q, $v) => $q->where('vendor_id', $v))
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
                'number' => $this->sequences->next('purchase_order', (int) $data->orderDate->format('Y')),
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
                $po->lines()->create($line->toArray());
            }

            return $po->fresh(['lines.item', 'vendor']);
        });
    }

    public function createFromRfq(RequestForQuotation $rfq, int $userId): PurchaseOrder
    {
        if ($rfq->status->value !== 'awarded') {
            throw PurchaseOrderException::rfqNotAwarded($rfq);
        }

        $award = $rfq->awardedQuotation;
        if (! $award) {
            throw PurchaseOrderException::rfqNotAwarded($rfq);
        }

        $lines = $award->lines->map(function ($ql, $i) {
            return new PurchaseOrderLineData(
                itemId: $ql->item_id,
                unitId: null,
                quantity: (string) $ql->quantity,
                unitPrice: (string) $ql->unit_price,
                taxRate: (string) $ql->tax_rate,
                requiredDate: $rfq->due_date?->toDateString(),
                specification: null,
                rfqLineId: $ql->rfq_line_id,
                position: $i,
            );
        })->all();

        return DB::transaction(function () use ($rfq, $award, $lines, $userId) {
            $po = $this->create(new PurchaseOrderData(
                vendorId: $award->vendor_id,
                orderDate: now(),
                currencyCode: $rfq->currency_code,
                lines: $lines,
                expectedDate: now()->addDays((int) ($award->lead_time_days ?? 7)),
                departmentId: $rfq->department_id,
                costCenterId: $rfq->cost_center_id,
                reference: "From RFQ {$rfq->number}",
                notes: "Auto-generated from awarded RFQ {$rfq->number}",
            ), $userId);

            $po->forceFill([
                'source_type' => RequestForQuotation::class,
                'source_id' => $rfq->id,
            ])->save();

            return $po->fresh();
        });
    }

    public function update(PurchaseOrder $po, PurchaseOrderData $data, int $userId): PurchaseOrder
    {
        if (! $po->status->isEditable()) {
            throw PurchaseOrderException::notEditable($po);
        }

        return DB::transaction(function () use ($po, $data, $userId) {
            $po->lines()->delete();

            foreach ($data->lines as $line) {
                $po->lines()->create($line->toArray());
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
            'notes' => $reason ? trim(($po->notes ?? '')."\nCancelled: ".$reason) : $po->notes,
            'updated_by' => $userId,
        ])->save();

        return $po->fresh();
    }

    public function close(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        if (! in_array($po->status, [PurchaseOrderStatus::Received, PurchaseOrderStatus::PartiallyReceived], true)) {
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
     * Recompute received_quantity per line from posted GRNs and update PO status.
     * Called by GoodsReceiptService after a GRN posts.
     */
    public function recalculateReceiptStatus(PurchaseOrder $po): void
    {
        DB::transaction(function () use ($po) {
            /** @var PurchaseOrder $locked */
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($po->id);

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

            $locked->refresh();

            if ($locked->status->acceptsReceipt()) {
                if ($locked->isFullyReceived()) {
                    $locked->forceFill(['status' => PurchaseOrderStatus::Received])->save();
                } elseif ($locked->isPartiallyReceived()) {
                    $locked->forceFill(['status' => PurchaseOrderStatus::PartiallyReceived])->save();
                }
            }
        });
    }
}
