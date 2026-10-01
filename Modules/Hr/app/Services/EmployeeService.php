<?php

declare(strict_types=1);

namespace Modules\Hr\Services;

use App\Contracts\SequenceGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Hr\Contracts\EmployeeManager;
use Modules\Hr\Models\Employee;

final class EmployeeService implements EmployeeManager
{
    public function __construct(private readonly SequenceGenerator $sequences) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->when($filters['search'] ?? null, function ($query, string $term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('number', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, int $userId): Employee
    {
        return DB::transaction(function () use ($data, $userId): Employee {
            $employee = Employee::query()->create([
                ...collect($data)->except(['contract_type', 'contract_end_date', 'monthly_salary'])->all(),
                'number' => $this->sequences->next('employee'),
                'status' => 'onboarding',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $employee->contracts()->create([
                'type' => $data['contract_type'],
                'start_date' => $data['joining_date'],
                'end_date' => $data['contract_end_date'] ?? null,
                'monthly_salary' => $data['monthly_salary'],
                'is_current' => true,
            ]);

            return $employee->fresh('contracts');
        });
    }

    public function update(Employee $employee, array $data, int $userId): Employee
    {
        $employee->update([...$data, 'updated_by' => $userId]);

        return $employee->fresh();
    }

    public function activate(Employee $employee, int $userId): Employee
    {
        $employee->update(['status' => 'active', 'updated_by' => $userId]);

        return $employee->fresh();
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
    }
}
