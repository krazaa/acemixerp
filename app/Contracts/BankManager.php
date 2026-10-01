<?php

namespace App\Contracts;

use App\Enums\RecordStatus;
use App\Models\Bank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BankManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allActive(): Collection;

    public function create(array $data): Bank;

    public function update(Bank $bank, array $data): Bank;

    public function delete(Bank $bank): void;

    public function changeStatus(Bank $bank, RecordStatus $status): Bank;
}
