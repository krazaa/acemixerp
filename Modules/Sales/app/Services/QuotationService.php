<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use App\Contracts\SequenceGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Contracts\QuotationManager;
use Modules\Sales\Data\QuotationData;
use Modules\Sales\Enums\QuotationStatus;
use Modules\Sales\Exceptions\QuotationException;
use Modules\Sales\Models\Quotation;

final class QuotationService implements QuotationManager
{
    public function __construct(private readonly SequenceGenerator $sequences) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Quotation::query()
            ->with(['customer:id,code,name', 'salesperson:id,name', 'creator:id,name'])
            ->withCount('lines')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['customer_id'] ?? null, fn ($q, $c) => $q->where('customer_id', $c))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('quotation_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('quotation_date', '<=', $d))
            ->orderByDesc('quotation_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(QuotationData $data, int $userId): Quotation
    {
        if (count($data->lines) === 0) {
            throw QuotationException::noLines();
        }

        return DB::transaction(function () use ($data, $userId) {
            $quotation = Quotation::query()->create([
                'number' => $this->sequences->next('quotation', (int) $data->quotationDate->format('Y')),
                'customer_id' => $data->customerId,
                'quotation_date' => $data->quotationDate,
                'valid_until' => $data->validUntil,
                'salesperson_id' => $data->salespersonId ?? $userId,
                'department_id' => $data->departmentId,
                'warehouse_id' => $data->warehouseId,
                'payment_term_id' => $data->paymentTermId,
                'currency_code' => $data->currencyCode,
                'exchange_rate' => $data->exchangeRate,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'terms' => $data->terms,
                'status' => QuotationStatus::Draft,
                'subtotal' => $data->subtotal(),
                'discount_total' => $data->discountTotal(),
                'tax_total' => $data->taxTotal(),
                'wht_tax_total' => $data->whtTaxTotal(),
                'total' => $data->total(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data->lines as $line) {
                $quotation->lines()->create($line->toArray());
            }

            return $quotation->fresh(['lines.item', 'customer']);
        });
    }

    public function update(Quotation $quotation, QuotationData $data, int $userId): Quotation
    {
        if (! $quotation->status->isEditable()) {
            throw QuotationException::notEditable($quotation);
        }

        return DB::transaction(function () use ($quotation, $data, $userId) {
            $quotation->lines()->delete();

            foreach ($data->lines as $line) {
                $quotation->lines()->create($line->toArray());
            }

            $quotation->fill([
                'customer_id' => $data->customerId,
                'quotation_date' => $data->quotationDate,
                'valid_until' => $data->validUntil,
                'salesperson_id' => $data->salespersonId ?? $quotation->salesperson_id,
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

            return $quotation->fresh(['lines.item', 'customer']);
        });
    }

    public function send(Quotation $quotation, int $userId): Quotation
    {
        if (! $quotation->status->canSend()) {
            throw QuotationException::invalidTransition($quotation, 'send');
        }

        $quotation->forceFill([
            'status' => QuotationStatus::Sent,
            'sent_at' => now(),
            'sent_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $quotation->fresh();
    }

    public function accept(Quotation $quotation, int $userId): Quotation
    {
        if (! $quotation->status->canAccept()) {
            throw QuotationException::invalidTransition($quotation, 'accept');
        }

        $quotation->forceFill([
            'status' => QuotationStatus::Accepted,
            'accepted_at' => now(),
            'accepted_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        return $quotation->fresh();
    }

    public function reject(Quotation $quotation, int $userId, ?string $reason = null): Quotation
    {
        if (! $quotation->status->canReject()) {
            throw QuotationException::invalidTransition($quotation, 'reject');
        }

        $quotation->forceFill([
            'status' => QuotationStatus::Rejected,
            'notes' => $reason ? trim(($quotation->notes ?? '')."\nRejected: ".$reason) : $quotation->notes,
            'updated_by' => $userId,
        ])->save();

        return $quotation->fresh();
    }

    public function cancel(Quotation $quotation, int $userId, ?string $reason = null): Quotation
    {
        if (! $quotation->status->canCancel()) {
            throw QuotationException::invalidTransition($quotation, 'cancel');
        }

        $quotation->forceFill([
            'status' => QuotationStatus::Cancelled,
            'notes' => $reason ? trim(($quotation->notes ?? '')."\nCancelled: ".$reason) : $quotation->notes,
            'updated_by' => $userId,
        ])->save();

        return $quotation->fresh();
    }

    public function expireOverdue(?int $userId = null): int
    {
        return Quotation::query()
            ->where('status', QuotationStatus::Sent->value)
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now()->toDateString())
            ->update(['status' => QuotationStatus::Expired->value]);
    }

    public function delete(Quotation $quotation): void
    {
        if ($quotation->status !== QuotationStatus::Draft) {
            throw QuotationException::cannotDelete($quotation);
        }
        DB::transaction(fn () => $quotation->delete());
    }
}
