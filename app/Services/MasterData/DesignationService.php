<?php

namespace App\Services\MasterData;

use App\Contracts\DesignationManager;
use App\Enums\RecordStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Designation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DesignationService implements DesignationManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Designation::query()
            ->with(['department:id,code,name'])
            ->withCount('users')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['department_id'] ?? null, fn ($q, $d) => $q->where('department_id', $d))
            ->orderBy('level')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(?int $departmentId = null): Collection
    {
        return Designation::query()
            ->active()
            ->when($departmentId !== null, fn ($q) => $q->where(fn ($q) => $q
                ->where('department_id', $departmentId)
                ->orWhereNull('department_id')))
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'department_id', 'level']);
    }

    public function create(array $data): Designation
    {
        return DB::transaction(function () use ($data) {
            return Designation::query()->create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'level' => $data['level'] ?? 100,
                'status' => $data['status'] ?? RecordStatus::Active,
            ]);
        });
    }

    public function update(Designation $designation, array $data): Designation
    {
        return DB::transaction(function () use ($designation, $data) {
            $designation->fill([
                'code' => $data['code'] ?? $designation->code,
                'name' => $data['name'] ?? $designation->name,
                'description' => $data['description'] ?? $designation->description,
                'department_id' => array_key_exists('department_id', $data)
                                    ? $data['department_id']
                                    : $designation->department_id,
                'level' => $data['level'] ?? $designation->level,
            ])->save();

            return $designation->fresh();
        });
    }

    public function delete(Designation $designation): void
    {
        if ($designation->isReferenced()) {
            throw BusinessRuleException::make(
                'Designation cannot be deleted while assigned to users. '.
                'Reassign them or archive the designation.'
            );
        }

        DB::transaction(fn () => $designation->delete());
    }

    public function changeStatus(Designation $designation, RecordStatus $status): Designation
    {
        if (! $designation->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                'Cannot transition designation status from '.
                "{$designation->status->value} to {$status->value}."
            );
        }

        // Guard: cannot deactivate while still assigned to active users.
        if ($status !== RecordStatus::Active && $designation->users()->exists()) {
            throw BusinessRuleException::make(
                'Cannot deactivate a designation currently assigned to users. Reassign them first.'
            );
        }

        $designation->forceFill(['status' => $status])->save();

        return $designation->fresh();
    }
}
