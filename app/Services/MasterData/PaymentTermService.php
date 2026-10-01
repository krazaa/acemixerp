<?php

declare(strict_types=1);

namespace App\Services\MasterData;

use App\Contracts\PaymentTermManager;
use App\Enums\PaymentTermType;
use App\Enums\RecordStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\PaymentTerm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class PaymentTermService implements PaymentTermManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return PaymentTerm::query()
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(): Collection
    {
        return PaymentTerm::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type', 'days', 'day_of_month', 'is_default']);
    }

    public function create(array $data): PaymentTerm
    {
        $this->validateTypeFields($data);

        return DB::transaction(function () use ($data) {
            $term = PaymentTerm::query()->create([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'type' => $data['type'],
                'days' => $data['days'] ?? 0,
                'day_of_month' => $data['day_of_month'] ?? null,
                'discount_percent' => $data['discount_percent'] ?? null,
                'discount_days' => $data['discount_days'] ?? null,
                'is_default' => false,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? RecordStatus::Active,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if (! empty($data['is_default'])) {
                $this->makeDefault($term);
            }

            return $term->fresh();
        });
    }

    public function update(PaymentTerm $term, array $data): PaymentTerm
    {
        $merged = array_merge($term->toArray(), $data);
        $this->validateTypeFields($merged);

        return DB::transaction(function () use ($term, $data) {
            $term->fill([
                'code' => isset($data['code']) ? strtoupper($data['code']) : $term->code,
                'name' => $data['name'] ?? $term->name,
                'type' => $data['type'] ?? $term->type,
                'days' => $data['days'] ?? $term->days,
                'day_of_month' => $data['day_of_month'] ?? $term->day_of_month,
                'discount_percent' => $data['discount_percent'] ?? $term->discount_percent,
                'discount_days' => $data['discount_days'] ?? $term->discount_days,
                'description' => $data['description'] ?? $term->description,
                'updated_by' => Auth::id(),
            ])->save();

            if (array_key_exists('is_default', $data) && $data['is_default']) {
                $this->makeDefault($term);
            }

            return $term->fresh();
        });
    }

    public function delete(PaymentTerm $term): void
    {
        if ($term->is_default) {
            throw BusinessRuleException::make(
                'Cannot delete the default payment term. Assign a new default first.'
            );
        }

        DB::transaction(fn () => $term->delete());
    }

    public function changeStatus(PaymentTerm $term, RecordStatus $status): PaymentTerm
    {
        if (! $term->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition payment term status from {$term->status->value} to {$status->value}."
            );
        }

        if ($status !== RecordStatus::Active && $term->is_default) {
            throw BusinessRuleException::make(
                'Cannot deactivate the default payment term. Assign a new default first.'
            );
        }

        $term->forceFill([
            'status' => $status,
            'updated_by' => Auth::id(),
        ])->save();

        return $term->fresh();
    }

    public function makeDefault(PaymentTerm $term): PaymentTerm
    {
        if ($term->status !== RecordStatus::Active) {
            throw BusinessRuleException::make('Only active payment terms can be set as default.');
        }

        return DB::transaction(function () use ($term) {
            PaymentTerm::query()
                ->where('is_default', true)
                ->where('id', '!=', $term->id)
                ->update(['is_default' => false]);

            $term->forceFill(['is_default' => true])->save();

            return $term->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws BusinessRuleException
     */
    private function validateTypeFields(array $data): void
    {
        $type = PaymentTermType::tryFrom((string) ($data['type'] ?? 'net'))
            ?? PaymentTermType::Net;

        if ($type === PaymentTermType::DayOfMonth) {
            $day = $data['day_of_month'] ?? null;
            if (! $day || (int) $day < 1 || (int) $day > 31) {
                throw BusinessRuleException::make(
                    'Day of month must be between 1 and 31 for this payment term type.'
                );
            }
        }

        if (($type === PaymentTermType::Net || $type === PaymentTermType::EndOfMonth)
            && (int) ($data['days'] ?? 0) < 0) {
            throw BusinessRuleException::make('Days must be a non-negative integer.');
        }

        if (isset($data['discount_percent']) && $data['discount_percent'] !== null) {
            $pct = (float) $data['discount_percent'];
            if ($pct < 0 || $pct > 100) {
                throw BusinessRuleException::make('Discount percent must be between 0 and 100.');
            }
        }
    }
}
