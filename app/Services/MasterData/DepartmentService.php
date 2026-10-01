<?php

declare(strict_types=1);

namespace App\Services\MasterData;

use App\Contracts\DepartmentManager;
use App\Enums\RecordStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DepartmentService implements DepartmentManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Department::query()
            ->with(['parent:id,name', 'manager:id,name'])
            ->withCount(['users', 'designations', 'costCenters'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(): Collection
    {
        return Department::query()->active()->orderBy('name')->get(['id', 'code', 'name']);
    }

    public function create(array $data): Department
    {
        return DB::transaction(function () use ($data) {
            return Department::query()->create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'manager_id' => $data['manager_id'] ?? null,
                'status' => $data['status'] ?? RecordStatus::Active,
            ]);
        });
    }

    public function update(Department $dept, array $data): Department
    {
        return DB::transaction(function () use ($dept, $data) {
            if (isset($data['parent_id']) && $data['parent_id'] !== $dept->parent_id) {
                $this->guardCycle($dept, (int) $data['parent_id']);
            }

            $dept->fill([
                'code' => $data['code'] ?? $dept->code,
                'name' => $data['name'] ?? $dept->name,
                'description' => $data['description'] ?? $dept->description,
                'parent_id' => $data['parent_id'] ?? $dept->parent_id,
                'manager_id' => $data['manager_id'] ?? $dept->manager_id,
            ])->save();

            return $dept->fresh();
        });
    }

    public function delete(Department $dept): void
    {
        if ($dept->isReferenced()) {
            throw BusinessRuleException::make(
                'This department is referenced by users, designations, cost centers, or sub-departments. '.
                'Archive it instead of deleting.'
            );
        }

        DB::transaction(fn () => $dept->delete());
    }

    public function changeStatus(Department $dept, RecordStatus $status): Department
    {
        if (! $dept->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition from {$dept->status->value} to {$status->value}."
            );
        }
        $dept->forceFill(['status' => $status])->save();

        return $dept->fresh();
    }

    private function guardCycle(Department $dept, int $newParentId): void
    {
        if ($newParentId === $dept->id) {
            throw BusinessRuleException::make('A department cannot be its own parent.');
        }

        if (in_array($newParentId, $dept->descendantIds(), true)) {
            throw BusinessRuleException::make(
                'Cannot set a descendant as the parent (would create a cycle).'
            );
        }
    }
}
