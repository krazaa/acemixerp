<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Contracts\SystemAccountManager;
use App\Enums\SystemAccountRole;
use App\Exceptions\BusinessRuleException;
use App\Models\Account;
use App\Models\SystemAccount;
use Illuminate\Support\Facades\DB;

final class SystemAccountService implements SystemAccountManager
{
    public function map(SystemAccountRole $role, Account $account): void
    {
        if (! $account->is_postable) {
            throw BusinessRuleException::make(
                "Account [{$account->code}] is not postable and cannot be mapped as a system account."
            );
        }

        DB::transaction(function () use ($role, $account) {
            SystemAccount::query()->updateOrCreate(
                ['role' => $role->value],
                ['account_id' => $account->id],
            );
        });

        cache()->forget('system_accounts.mappings');
    }

    public function resolve(SystemAccountRole $role): ?Account
    {
        $mappings = $this->all();

        if (! isset($mappings[$role->value])) {
            return null;
        }

        return Account::query()->find($mappings[$role->value]);
    }

    public function all(): array
    {
        return cache()->rememberForever(
            'system_accounts.mappings',
            fn () => SystemAccount::allMappings(),
        );
    }

    public function missingRequiredRoles(): array
    {
        $mapped = array_keys($this->all());

        return collect(SystemAccountRole::cases())
            ->filter(fn (SystemAccountRole $r) => $r->isRequired() && ! in_array($r->value, $mapped, true))
            ->map(fn (SystemAccountRole $r) => $r->value)
            ->all();
    }
}
