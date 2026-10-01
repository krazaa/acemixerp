<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\AccountManager;
use App\Contracts\SystemAccountManager;
use App\Enums\SystemAccountRole;
use App\Models\Account;
use App\Models\SystemAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemAccountController extends Controller
{
    public function __construct(
        private readonly SystemAccountManager $systemAccounts,
        private readonly AccountManager $accounts,
    ) {}

    public function edit(): View
    {
        $this->authorize('coa.manage');

        return view('system-accounts.edit', [
            'roles' => SystemAccountRole::cases(),
            'mappings' => $this->systemAccounts->all(),
            'missing' => $this->systemAccounts->missingRequiredRoles(),
            'accounts' => $this->accounts->allPostable(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('coa.manage');

        $validated = $request->validate([
            'mappings' => ['required', 'array'],
            'mappings.*' => ['nullable', 'integer', 'exists:accounts,id'],
        ]);

        foreach ($validated['mappings'] as $roleValue => $accountId) {
            $role = SystemAccountRole::tryFrom((string) $roleValue);
            if (! $role) {
                continue;
            }

            if (! $accountId) {
                SystemAccount::query()->where('role', $role->value)->delete();

                continue;
            }

            $account = Account::query()->findOrFail((int) $accountId);
            $this->systemAccounts->map($role, $account);
        }

        cache()->forget('system_accounts.mappings');

        return redirect()->route('system-accounts.edit')
            ->with('status', 'System account mappings updated.');
    }
}
