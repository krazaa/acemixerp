<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use App\Contracts\SequenceGenerator;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Contracts\CreditCheckService;
use Modules\Sales\Contracts\SalesOrderManager;
use Modules\Sales\Data\SalesOrderData;
use Modules\Sales\Data\SalesOrderLineData;
use Modules\Sales\Enums\QuotationStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Exceptions\SalesOrderException;
use Modules\Sales\Models\DeliveryLine;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\SalesOrder;

final class SalesOrderService implements SalesOrderManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly CreditCheckService $credit,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return SalesOrder::query()
            ->with(['customer:id,code,name', 'salesperson:id,first_name,last_name', 'approver:id,name'])
            ->withCount('lines')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['customer_id'] ?? null, fn ($q, $c) => $q->where('customer_id', $c))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('order_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('order_date', '<=', $d))
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(SalesOrderData $data, int $userId): SalesOrder
    {
        if (count($data->lines) === 0) {
            throw SalesOrderException::noLines();
        }

        return DB::transaction(function () use ($data, $userId) {
            $order = SalesOrder::query()->create([
                'number' => $this->sequences->next('sales_order', (int) $data->orderDate->format('Y')),
                'customer_id' => $data->customerId,
                'order_date' => $data->orderDate,
                'expected_delivery_date' => $data->expectedDeliveryDate,
                'salesperson_id' => $data->salespersonId ?? $userId,
                'department_id' => $data->departmentId,
                'warehouse_id' => $data->warehouseId,
                'payment_term_id' => $data->paymentTermId,
                'currency_code' => $data->currencyCode,
                'exchange_rate' => $data->exchangeRate,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'terms' => $data->terms,
                'status' => SalesOrderStatus::Draft,
                'subtotal' => $data->subtotal(),
                'discount_total' => $data->discountTotal(),
                'tax_total' => $data->taxTotal(),
                'wht_tax_total' => $data->whtTaxTotal(),
                'total' => $data->total(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data->lines as $line) {
                $order->lines()->create($line->toArray());
            }

            return $order->fresh(['lines.item', 'customer']);
        });
    }

    public function createFromQuotation(Quotation $quotation, int $userId): SalesOrder
    {
        if (! $quotation->status->canConvert()) {
            throw SalesOrderException::quotationNotConvertible($quotation);
        }

        $lines = $quotation->lines->values()->map(function ($ql, int $i) {
            return new SalesOrderLineData(
                itemId: $ql->item_id,
                unitId: $ql->unit_id,
                quantity: (string) $ql->quantity,
                unitPrice: (string) $ql->unit_price,
                discountPercent: (string) $ql->discount_percent,
                taxRate: (string) $ql->tax_rate,
                whtTaxRate: (string) $ql->wht_tax_rate,
                description: $ql->description,
                quotationLineId: $ql->id,
                position: $i,
            );
        })->all();

        return DB::transaction(function () use ($quotation, $lines, $userId) {
            $order = $this->create(new SalesOrderData(
                customerId: $quotation->customer_id,
                orderDate: now(),
                currencyCode: $quotation->currency_code,
                lines: $lines,
                salespersonId: $quotation->salesperson_id,
                departmentId: $quotation->department_id,
                warehouseId: $quotation->warehouse_id,
                paymentTermId: $quotation->payment_term_id,
                reference: $quotation->reference,
                notes: "Generated from quotation {$quotation->number}",
                terms: $quotation->terms,
                sourceQuotationId: $quotation->id,
            ), $userId);

            $order->forceFill([
                'source_type' => Quotation::class,
                'source_id' => $quotation->id,
            ])->save();

            $quotation->forceFill([
                'status' => QuotationStatus::Converted,
                'converted_to_type' => SalesOrder::class,
                'converted_to_id' => $order->id,
            ])->save();

            return $order->fresh();
        });
    }

    public function update(SalesOrder $order, SalesOrderData $data, int $userId): SalesOrder
    {
        if (! $order->status->isEditable()) {
            throw SalesOrderException::notEditable($order);
        }

        return DB::transaction(function () use ($order, $data, $userId) {
            $existingLines = $order->lines()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $submittedLineIds = collect($data->lines)
                ->pluck('salesOrderLineId')
                ->filter()
                ->map(fn (int $lineId) => $lineId);

            if ($submittedLineIds->diff($existingLines->keys())->isNotEmpty()) {
                throw SalesOrderException::lineDoesNotBelong($order);
            }

            $removedLineIds = $existingLines->keys()->diff($submittedLineIds);

            if ($removedLineIds->isNotEmpty() && DeliveryLine::query()
                ->whereIn('sales_order_line_id', $removedLineIds)
                ->exists()) {
                throw SalesOrderException::cannotRemoveDeliveredLine($order);
            }

            $order->lines()->whereKey($removedLineIds)->delete();

            foreach ($data->lines as $line) {
                if ($line->salesOrderLineId) {
                    $existingLines->get($line->salesOrderLineId)?->update($line->toArray());

                    continue;
                }

                $order->lines()->create($line->toArray());
            }

            $order->fill([
                'customer_id' => $data->customerId,
                'order_date' => $data->orderDate,
                'expected_delivery_date' => $data->expectedDeliveryDate,
                'salesperson_id' => $data->salespersonId ?? $order->salesperson_id,
                'department_id' => $data->departmentId,
                'warehouse_id' => $data->warehouseId,
                'payment_term_id' => $data->paymentTermId,
                'currency_code' => $data->currencyCode,
                'exchange_rate' => $data->exchangeRate,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'terms' => $data->terms,
                'subtotal' => $data->subtotal(),
                'discount_total' => $data->discountTotal(),
                'tax_total' => $data->taxTotal(),
                'wht_tax_total' => $data->whtTaxTotal(),
                'total' => $data->total(),
                'updated_by' => $userId,
            ])->save();

            return $order->fresh(['lines.item', 'customer']);
        });
    }

    /**
     * Submitting runs the credit check. If credit is exceeded, the order
     * goes to `on_hold` and returns successfully — a manager must release it.
     */
    public function submit(SalesOrder $order, int $userId): SalesOrder
    {
        if (! $order->status->canSubmit()) {
            throw SalesOrderException::invalidTransition($order, 'submit');
        }

        if ($order->lines()->count() === 0) {
            throw SalesOrderException::noLines();
        }

        return DB::transaction(function () use ($order, $userId) {
            /** @var SalesOrder $locked */
            $locked = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);

            $customer = Customer::query()->lockForUpdate()->findOrFail($locked->customer_id);

            $outstanding = $this->credit->outstandingAr($customer);
            $canExtend = $this->credit->canExtend($customer, (string) $locked->total);

            $locked->forceFill([
                'credit_limit_at_submission' => $customer->credit_limit,
                'outstanding_ar_at_submission' => $outstanding,
                'submitted_at' => now(),
                'submitted_by' => $userId,
                'updated_by' => $userId,
            ]);

            if (! $canExtend) {
                $locked->forceFill([
                    'status' => SalesOrderStatus::OnHold,
                    'credit_hold_at' => now(),
                    'credit_hold_reason' => sprintf(
                        'Credit limit %s exceeded. Exposure %s + new order %s > limit.',
                        $customer->credit_limit,
                        $outstanding,
                        $locked->total,
                    ),
                ])->save();
            } else {
                $locked->forceFill([
                    'status' => SalesOrderStatus::Submitted,
                ])->save();
            }

            return $locked->fresh();
        });
    }

    public function approve(SalesOrder $order, int $userId): SalesOrder
    {
        if (! $order->status->canApprove()) {
            throw SalesOrderException::invalidTransition($order, 'approve');
        }

        if ($order->submitted_by === $userId) {
            throw SalesOrderException::cannotSelfApprove($order);
        }

        $order->forceFill([
            'status' => SalesOrderStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $order->fresh();
    }

    public function reject(SalesOrder $order, int $userId, string $reason): SalesOrder
    {
        if ($order->status !== SalesOrderStatus::Submitted) {
            throw SalesOrderException::invalidTransition($order, 'reject');
        }

        $order->forceFill([
            'status' => SalesOrderStatus::Rejected,
            'notes' => trim(($order->notes ?? '')."\nRejected: ".$reason),
            'updated_by' => $userId,
        ])->save();

        return $order->fresh();
    }

    /**
     * Release a credit hold: the manager has decided to extend credit
     * beyond the customer's limit. Records who released it and why.
     */
    public function releaseHold(SalesOrder $order, int $userId, string $reason): SalesOrder
    {
        if (! $order->status->canReleaseHold()) {
            throw SalesOrderException::invalidTransition($order, 'release hold');
        }

        $order->forceFill([
            'status' => SalesOrderStatus::Submitted,
            'credit_hold_released_by' => $userId,
            'credit_hold_reason' => trim(($order->credit_hold_reason ?? '')."\nReleased by {$userId}: ".$reason),
            'updated_by' => $userId,
        ])->save();

        return $order->fresh();
    }

    public function confirm(SalesOrder $order, int $userId): SalesOrder
    {
        if (! $order->status->canConfirm()) {
            throw SalesOrderException::invalidTransition($order, 'confirm');
        }

        $order->forceFill([
            'status' => SalesOrderStatus::Confirmed,
            'confirmed_at' => now(),
            'confirmed_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $order->fresh();
    }

    public function cancel(SalesOrder $order, int $userId, ?string $reason = null): SalesOrder
    {
        if (! $order->status->canCancel()) {
            throw SalesOrderException::invalidTransition($order, 'cancel');
        }

        $order->forceFill([
            'status' => SalesOrderStatus::Cancelled,
            'notes' => $reason ? trim(($order->notes ?? '')."\nCancelled: ".$reason) : $order->notes,
            'updated_by' => $userId,
        ])->save();

        return $order->fresh();
    }

    public function close(SalesOrder $order, int $userId): SalesOrder
    {
        if (! in_array($order->status, [SalesOrderStatus::Delivered, SalesOrderStatus::PartiallyDelivered], true)) {
            throw SalesOrderException::invalidTransition($order, 'close');
        }

        $order->forceFill([
            'status' => SalesOrderStatus::Closed,
            'updated_by' => $userId,
        ])->save();

        return $order->fresh();
    }

    public function delete(SalesOrder $order): void
    {
        if ($order->status !== SalesOrderStatus::Draft) {
            throw SalesOrderException::cannotDelete($order);
        }
        DB::transaction(fn () => $order->delete());
    }
}
