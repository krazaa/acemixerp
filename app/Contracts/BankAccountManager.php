<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\RecordStatus;
use App\Models\BankAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BankAccountManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allActive(): Collection;

    public function create(array $data): BankAccount;

    public function update(BankAccount $account, array $data): BankAccount;

    public function delete(BankAccount $account): void;

    public function changeStatus(BankAccount $account, RecordStatus $status): BankAccount;

    public function makeDefault(BankAccount $account): BankAccount;

    public function currentBalance(BankAccount $account): string;
}
