<?php

namespace App\Contracts;

use App\Enums\RecordStatus;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UnitManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allActive(): Collection;

    public function create(array $data): Unit;

    public function update(Unit $unit, array $data): Unit;

    public function delete(Unit $unit): void;

    public function changeStatus(Unit $unit, RecordStatus $status): Unit;
}
