<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\RecordStatus;
use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DepartmentManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allActive(): Collection;

    public function create(array $data): Department;

    public function update(Department $dept, array $data): Department;

    public function delete(Department $dept): void;

    public function changeStatus(Department $dept, RecordStatus $status): Department;
}
