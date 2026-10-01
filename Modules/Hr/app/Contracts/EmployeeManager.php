<?php

declare(strict_types=1);

namespace Modules\Hr\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Hr\Models\Employee;

interface EmployeeManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(array $data, int $userId): Employee;

    public function update(Employee $employee, array $data, int $userId): Employee;

    public function activate(Employee $employee, int $userId): Employee;

    public function delete(Employee $employee): void;
}
