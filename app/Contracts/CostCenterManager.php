<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\RecordStatus;
use App\Models\CostCenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CostCenterManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allActive(?int $departmentId = null): Collection;

    public function create(array $data): CostCenter;

    public function update(CostCenter $costCenter, array $data): CostCenter;

    public function delete(CostCenter $costCenter): void;

    public function changeStatus(CostCenter $costCenter, RecordStatus $status): CostCenter;
}
