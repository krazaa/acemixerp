<?php

declare(strict_types=1);

namespace Modules\Procurement\Services;

use App\Contracts\SequenceGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Procurement\Contracts\RfqManager;
use Modules\Procurement\Data\RfqData;
use Modules\Procurement\Enums\RfqStatus;
use Modules\Procurement\Enums\RfqVendorStatus;
use Modules\Procurement\Exceptions\RfqException;
use Modules\Procurement\Models\RequestForQuotation;

final class RfqService implements RfqManager
{
    public function __construct(private readonly SequenceGenerator $sequences, private readonly LineBrandService $brands) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return RequestForQuotation::query()
            ->with(['department:id,name', 'creator:id,name'])
            ->withCount(['lines', 'vendors', 'quotations'])
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['department_id'] ?? null, fn ($q, $d) => $q->where('department_id', $d))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('issue_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('issue_date', '<=', $d))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(RfqData $data, int $userId): RequestForQuotation
    {
        if (count($data->lines) === 0) {
            throw RfqException::noLines();
        }
        if (count($data->vendorIds) === 0) {
            throw RfqException::noVendors();
        }

        return DB::transaction(function () use ($data, $userId) {
            $rfq = RequestForQuotation::query()->create([
                'number' => $this->sequences->next('rfq', (int) $data->issueDate->format('Y')),
                'issue_date' => $data->issueDate,
                'due_date' => $data->dueDate,
                'department_id' => $data->departmentId,
                'cost_center_id' => $data->costCenterId,
                'currency_code' => strtoupper($data->currencyCode),
                'purpose' => $data->purpose,
                'terms' => $data->terms,
                'status' => RfqStatus::Draft,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data->lines as $line) {
                $rfq->lines()->create(array_replace($line->toArray(), ['brand_id' => $this->brands->resolve($line->brandId, $line->position)]));
            }

            $this->syncVendors($rfq, $data->vendorIds);

            if ($data->sourceRequisitionIds !== []) {
                $rfq->sourceRequisitions()->sync($data->sourceRequisitionIds);
            }

            return $rfq->fresh(['lines.item', 'vendors.vendor']);
        });
    }

    public function update(RequestForQuotation $rfq, RfqData $data, int $userId): RequestForQuotation
    {
        if (! $rfq->status->isEditable()) {
            throw RfqException::notEditable($rfq);
        }

        return DB::transaction(function () use ($rfq, $data, $userId) {
            $rfq->lines()->delete();

            foreach ($data->lines as $line) {
                $rfq->lines()->create(array_replace($line->toArray(), ['brand_id' => $this->brands->resolve($line->brandId, $line->position)]));
            }

            $this->syncVendors($rfq, $data->vendorIds);

            $rfq->fill([
                'issue_date' => $data->issueDate,
                'due_date' => $data->dueDate,
                'department_id' => $data->departmentId,
                'cost_center_id' => $data->costCenterId,
                'currency_code' => strtoupper($data->currencyCode),
                'purpose' => $data->purpose,
                'terms' => $data->terms,
                'updated_by' => $userId,
            ])->save();

            return $rfq->fresh(['lines.item', 'vendors.vendor']);
        });
    }

    public function issue(RequestForQuotation $rfq, int $userId): RequestForQuotation
    {
        if (! $rfq->status->canIssue()) {
            throw RfqException::invalidTransition($rfq, 'issue');
        }

        if ($rfq->vendors()->count() === 0) {
            throw RfqException::noVendors();
        }

        $rfq->forceFill([
            'status' => RfqStatus::Issued,
            'issued_at' => now(),
            'issued_by' => $userId,
            'updated_by' => $userId,
        ])->save();

        // Mark all vendors as invited (they already are by default — but this is explicit)
        $rfq->vendors()->whereNull('invited_at')->update(['invited_at' => now()]);

        return $rfq->fresh();
    }

    public function startReceiving(RequestForQuotation $rfq, int $userId): RequestForQuotation
    {
        if ($rfq->status !== RfqStatus::Issued) {
            throw RfqException::invalidTransition($rfq, 'start receiving');
        }

        $rfq->forceFill([
            'status' => RfqStatus::Receiving,
            'updated_by' => $userId,
        ])->save();

        return $rfq->fresh();
    }

    public function cancel(RequestForQuotation $rfq, int $userId, ?string $reason = null): RequestForQuotation
    {
        if (! $rfq->status->canCancel()) {
            throw RfqException::invalidTransition($rfq, 'cancel');
        }

        $rfq->forceFill([
            'status' => RfqStatus::Cancelled,
            'updated_by' => $userId,
        ])->save();

        return $rfq->fresh();
    }

    public function close(RequestForQuotation $rfq, int $userId): RequestForQuotation
    {
        if (! in_array($rfq->status, [RfqStatus::Awarded], true)) {
            throw RfqException::invalidTransition($rfq, 'close');
        }

        $rfq->forceFill([
            'status' => RfqStatus::Closed,
            'updated_by' => $userId,
        ])->save();

        return $rfq->fresh();
    }

    public function delete(RequestForQuotation $rfq): void
    {
        if ($rfq->status !== RfqStatus::Draft) {
            throw RfqException::cannotDelete($rfq);
        }
        DB::transaction(fn () => $rfq->delete());
    }

    /** @param int[] $vendorIds */
    private function syncVendors(RequestForQuotation $rfq, array $vendorIds): void
    {
        $existing = $rfq->vendors()->pluck('vendor_id')->all();
        $toAdd = array_diff($vendorIds, $existing);
        $toRemove = array_diff($existing, $vendorIds);

        foreach ($toAdd as $vid) {
            $rfq->vendors()->create([
                'vendor_id' => (int) $vid,
                'status' => RfqVendorStatus::Invited,
                'invited_at' => $rfq->status === RfqStatus::Issued ? now() : null,
            ]);
        }

        // Only remove vendors that have not yet submitted
        foreach ($toRemove as $vid) {
            $rfq->vendors()
                ->where('vendor_id', $vid)
                ->where('status', RfqVendorStatus::Invited->value)
                ->delete();
        }
    }
}
