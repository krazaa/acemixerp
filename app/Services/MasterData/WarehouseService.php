<?php

declare(strict_types=1);

namespace App\Services\MasterData;

use App\Contracts\WarehouseManager;
use App\Enums\RecordStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class WarehouseService implements WarehouseManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Warehouse::query()
            ->with(['manager:id,name', 'department:id,code,name'])
            ->withCount('addresses')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(): Collection
    {
        return Warehouse::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type', 'is_default']);
    }

    public function allPickable(): Collection
    {
        return Warehouse::query()
            ->pickable()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type', 'is_default']);
    }

    public function create(array $data): Warehouse
    {
        return DB::transaction(function () use ($data) {
            $warehouse = Warehouse::query()->create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'main',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'manager_id' => $data['manager_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'is_default' => false, // flipped below if requested
                'inventory_account_id' => $data['inventory_account_id'] ?? null,
                'allow_negative_stock' => $data['allow_negative_stock'] ?? false,
                'is_pickable' => $data['is_pickable'] ?? true,
                'status' => $data['status'] ?? RecordStatus::Active,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if (! empty($data['addresses'])) {
                $warehouse->syncAddresses($data['addresses']);
            }

            if (! empty($data['is_default'])) {
                $this->makeDefault($warehouse);
            }

            return $warehouse->fresh(['addresses']);
        });
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data) {
            // Guard: cannot change type away from transit if there are pending
            // transfers — Phase 6 will enforce this properly. For now we only
            // validate that transit warehouses cannot be made pickable.
            $newType = $data['type'] ?? $warehouse->type->value;
            if ($newType === 'transit') {
                $data['is_pickable'] = false;
            }

            $warehouse->fill([
                'code' => $data['code'] ?? $warehouse->code,
                'name' => $data['name'] ?? $warehouse->name,
                'description' => $data['description'] ?? $warehouse->description,
                'type' => $data['type'] ?? $warehouse->type,
                'email' => $data['email'] ?? $warehouse->email,
                'phone' => $data['phone'] ?? $warehouse->phone,
                'manager_id' => array_key_exists('manager_id', $data) ? $data['manager_id'] : $warehouse->manager_id,
                'department_id' => array_key_exists('department_id', $data) ? $data['department_id'] : $warehouse->department_id,
                'cost_center_id' => array_key_exists('cost_center_id', $data) ? $data['cost_center_id'] : $warehouse->cost_center_id,
                'inventory_account_id' => array_key_exists('inventory_account_id', $data) ? $data['inventory_account_id'] : $warehouse->inventory_account_id,
                'allow_negative_stock' => $data['allow_negative_stock'] ?? $warehouse->allow_negative_stock,
                'is_pickable' => array_key_exists('is_pickable', $data) ? $data['is_pickable'] : $warehouse->is_pickable,
                'updated_by' => Auth::id(),
            ])->save();

            if (array_key_exists('addresses', $data)) {
                $warehouse->syncAddresses($data['addresses'] ?? []);
            }

            if (array_key_exists('is_default', $data) && $data['is_default']) {
                $this->makeDefault($warehouse);
            }

            return $warehouse->fresh(['addresses']);
        });
    }

    public function delete(Warehouse $warehouse): void
    {
        if ($warehouse->is_default) {
            throw BusinessRuleException::make(
                'Cannot delete the default warehouse. Assign another default first.'
            );
        }

        // Phase 6 will add: block if stock movements reference this warehouse.
        DB::transaction(fn () => $warehouse->delete());
    }

    public function changeStatus(Warehouse $warehouse, RecordStatus $status): Warehouse
    {
        if (! $warehouse->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition from {$warehouse->status->value} to {$status->value}."
            );
        }

        if ($status !== RecordStatus::Active && $warehouse->is_default) {
            throw BusinessRuleException::make(
                'Cannot deactivate the default warehouse. Assign another default first.'
            );
        }

        $warehouse->forceFill([
            'status' => $status,
            'updated_by' => Auth::id(),
        ])->save();

        return $warehouse->fresh();
    }

    /**
     * Flip the is_default flag atomically. Exactly one warehouse may be default.
     * Uses a transaction + UPDATE across all rows so concurrent calls cannot
     * leave two defaults in place.
     */
    public function makeDefault(Warehouse $warehouse): Warehouse
    {
        if (! $warehouse->type->canBeDefault()) {
            throw BusinessRuleException::make(
                "Warehouse type {$warehouse->type->label()} cannot be set as default."
            );
        }

        if ($warehouse->status !== RecordStatus::Active) {
            throw BusinessRuleException::make(
                'Only active warehouses can be set as default.'
            );
        }

        return DB::transaction(function () use ($warehouse) {
            // Clear current default, then set the new one. Two statements inside
            // one transaction so nobody observes an intermediate state.
            Warehouse::query()
                ->where('is_default', true)
                ->where('id', '!=', $warehouse->id)
                ->update(['is_default' => false]);

            $warehouse->forceFill(['is_default' => true])->save();

            return $warehouse->fresh();
        });
    }
}
