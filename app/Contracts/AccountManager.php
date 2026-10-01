<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\AccountType;
use App\Enums\RecordStatus;
use App\Models\Account;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AccountManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function allPostable(): Collection;

    public function tree(): Collection;

    public function create(array $data): Account;

    public function update(Account $account, array $data): Account;

    public function delete(Account $account): void;

    public function changeStatus(Account $account, RecordStatus $status): Account;

    public function findByCode(string $code): ?Account;

    public function nextCodeFor(AccountType $type): string;
}
