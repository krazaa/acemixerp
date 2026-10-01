<?php

namespace App\Services\MasterData;

use App\Contracts\CostCenterManager;
use App\Enums\RecordStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CostCenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CostCenterService implements CostCenterManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return CostCenter::query()
            ->with(['department:id,code,name'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['department_id'] ?? null, fn ($q, $d) => $q->where('department_id', $d))
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(?int $departmentId = null): Collection
    {
        return CostCenter::query()
            ->active()
            ->when($departmentId !== null, fn ($q) => $q->where(fn ($q) => $q
                ->where('department_id', $departmentId)
                ->orWhereNull('department_id')))
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'department_id']);
    }

    public function create(array $data): CostCenter
    {
        return DB::transaction(function () use ($data) {
            return CostCenter::query()->create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'default_expense_account_id' => $data['default_expense_account_id'] ?? null,
                'status' => $data['status'] ?? RecordStatus::Active,
            ]);
        });
    }

    public function update(CostCenter $costCenter, array $data): CostCenter
    {
        return DB::transaction(function () use ($costCenter, $data) {
            $costCenter->fill([
                'code' => $data['code'] ?? $costCenter->code,
                'name' => $data['name'] ?? $costCenter->name,
                'description' => $data['description'] ?? $costCenter->description,
                'department_id' => array_key_exists('department_id', $data)
                                    ? $data['department_id']
                                    : $costCenter->department_id,
                'default_expense_account_id' => array_key_exists('default_expense_account_id', $data)
                                    ? $data['default_expense_account_id']
                                    : $costCenter->default_expense_account_id,
            ])->save();

            return $costCenter->fresh();
        });
    }

    public function delete(CostCenter $costCenter): void
    {
        // Phase 3 will add a guard against `journal_lines.cost_center_id`
        // once the accounting module exists. Until then, deletion is
        // permitted but fully audited via LogsActivity.
        DB::transaction(fn () => $costCenter->delete());
    }

    public function changeStatus(CostCenter $costCenter, RecordStatus $status): CostCenter
    {
        if (! $costCenter->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                'Cannot transition cost center status from '.
                "{$costCenter->status->value} to {$status->value}."
            );
        }

        $costCenter->forceFill(['status' => $status])->save();

        return $costCenter->fresh();
    }
}
