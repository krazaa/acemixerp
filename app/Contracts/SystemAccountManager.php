<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\SystemAccountRole;
use App\Models\Account;

interface SystemAccountManager
{
    public function map(SystemAccountRole $role, Account $account): void;

    public function resolve(SystemAccountRole $role): ?Account;

    /** @return array<string, int> */
    public function all(): array;

    /** @return string[] Roles that are required but unmapped. */
    public function missingRequiredRoles(): array;
}
