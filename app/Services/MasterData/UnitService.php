<?php

namespace App\Services\MasterData;

use App\Contracts\UnitManager;
use App\Enums\RecordStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class UnitService implements UnitManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Unit::query()
            ->withCount('items')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(): Collection
    {
        return Unit::query()->active()->orderBy('code')->get(['id', 'code', 'name', 'quantity_precision']);
    }

    public function create(array $data): Unit
    {
        return DB::transaction(fn () => Unit::query()->create([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'quantity_precision' => $data['quantity_precision'] ?? 2,
            'status' => $data['status'] ?? RecordStatus::Active,
        ]));
    }

    public function update(Unit $unit, array $data): Unit
    {
        return DB::transaction(function () use ($unit, $data) {
            $unit->fill([
                'code' => isset($data['code']) ? strtoupper($data['code']) : $unit->code,
                'name' => $data['name'] ?? $unit->name,
                'description' => $data['description'] ?? $unit->description,
                'quantity_precision' => $data['quantity_precision'] ?? $unit->quantity_precision,
            ])->save();

            return $unit->fresh();
        });
    }

    public function delete(Unit $unit): void
    {
        if ($unit->isReferenced()) {
            throw BusinessRuleException::make(
                'Unit is used by one or more items. Archive it instead.'
            );
        }

        DB::transaction(fn () => $unit->delete());
    }

    public function changeStatus(Unit $unit, RecordStatus $status): Unit
    {
        if (! $unit->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition from {$unit->status->value} to {$status->value}."
            );
        }

        $unit->forceFill(['status' => $status])->save();

        return $unit->fresh();
    }
}
