<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\RecordStatus;
use App\Models\Designation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DesignationManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allActive(?int $departmentId = null): Collection;

    public function create(array $data): Designation;

    public function update(Designation $designation, array $data): Designation;

    public function delete(Designation $designation): void;

    public function changeStatus(Designation $designation, RecordStatus $status): Designation;
}
