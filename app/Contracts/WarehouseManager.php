<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\RecordStatus;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface WarehouseManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allActive(): Collection;

    public function allPickable(): Collection;

    public function create(array $data): Warehouse;

    public function update(Warehouse $warehouse, array $data): Warehouse;

    public function delete(Warehouse $warehouse): void;

    public function changeStatus(Warehouse $warehouse, RecordStatus $status): Warehouse;

    public function makeDefault(Warehouse $warehouse): Warehouse;
}
