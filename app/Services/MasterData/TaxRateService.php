<?php

namespace App\Services\MasterData;

use App\Contracts\TaxRateManager;
use App\Enums\RecordStatus;
use App\Enums\TaxRateComponent;
use App\Exceptions\BusinessRuleException;
use App\Models\TaxRate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class TaxRateService implements TaxRateManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return TaxRate::query()
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['component'] ?? null, fn ($q, $c) => $q->where('component', $c))
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(?TaxRateComponent $component = null): Collection
    {
        return TaxRate::query()
            ->active()
            ->when($component !== null, fn ($q) => $q->where('component', $component->value))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'rate', 'component', 'is_default']);
    }

    public function create(array $data): TaxRate
    {
        return DB::transaction(function () use ($data) {
            $taxRate = TaxRate::query()->create([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'type' => $data['type'] ?? 'standard',
                'component' => $data['component'] ?? 'output',
                'rate' => $data['rate'] ?? 0,
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
                'is_compound' => $data['is_compound'] ?? false,
                'is_recoverable' => $data['is_recoverable'] ?? true,
                'description' => $data['description'] ?? null,
                'is_default' => false, // flipped below if requested
                'status' => $data['status'] ?? RecordStatus::Active,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if (! empty($data['is_default'])) {
                $this->makeDefault($taxRate);
            }

            return $taxRate->fresh();
        });
    }

    public function update(TaxRate $taxRate, array $data): TaxRate
    {
        return DB::transaction(function () use ($taxRate, $data) {
            // Rate changes after effective_from should create a new row in
            // practice; we allow edits but audit them.
            $taxRate->fill([
                'code' => isset($data['code']) ? strtoupper($data['code']) : $taxRate->code,
                'name' => $data['name'] ?? $taxRate->name,
                'type' => $data['type'] ?? $taxRate->type,
                'component' => $data['component'] ?? $taxRate->component,
                'rate' => $data['rate'] ?? $taxRate->rate,
                'effective_from' => $data['effective_from'] ?? $taxRate->effective_from,
                'effective_to' => $data['effective_to'] ?? $taxRate->effective_to,
                'is_compound' => $data['is_compound'] ?? $taxRate->is_compound,
                'is_recoverable' => $data['is_recoverable'] ?? $taxRate->is_recoverable,
                'description' => $data['description'] ?? $taxRate->description,
                'updated_by' => Auth::id(),
            ])->save();

            if (array_key_exists('is_default', $data) && $data['is_default']) {
                $this->makeDefault($taxRate);
            }

            return $taxRate->fresh();
        });
    }

    public function delete(TaxRate $taxRate): void
    {
        if ($taxRate->is_default) {
            throw BusinessRuleException::make(
                'Cannot delete the default tax rate. Assign a new default first.'
            );
        }

        // Phase 3 will add reference checks.
        DB::transaction(fn () => $taxRate->delete());
    }

    public function changeStatus(TaxRate $taxRate, RecordStatus $status): TaxRate
    {
        if (! $taxRate->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition tax rate status from {$taxRate->status->value} to {$status->value}."
            );
        }

        if ($status !== RecordStatus::Active && $taxRate->is_default) {
            throw BusinessRuleException::make(
                'Cannot deactivate the default tax rate. Assign a new default first.'
            );
        }

        $taxRate->forceFill([
            'status' => $status,
            'updated_by' => Auth::id(),
        ])->save();

        return $taxRate->fresh();
    }

    public function makeDefault(TaxRate $taxRate): TaxRate
    {
        if ($taxRate->status !== RecordStatus::Active) {
            throw BusinessRuleException::make('Only active tax rates can be set as default.');
        }

        return DB::transaction(function () use ($taxRate) {
            TaxRate::query()
                ->where('component', $taxRate->component->value)
                ->where('is_default', true)
                ->where('id', '!=', $taxRate->id)
                ->update(['is_default' => false]);

            $taxRate->forceFill(['is_default' => true])->save();

            return $taxRate->fresh();
        });
    }
}
