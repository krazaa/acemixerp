<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Contracts\BankAccountManager;
use App\Contracts\LedgerService;
use App\Data\LedgerQuery;
use App\Enums\RecordStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Account;
use App\Models\BankAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class BankAccountService implements BankAccountManager
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return BankAccount::query()
            ->with(['bank:id,name,code', 'glAccount:id,code,name'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('account_number', 'like', "%{$s}%")
            ))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['bank_id'] ?? null, fn ($q, $b) => $q->where('bank_id', $b))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(): Collection
    {
        return BankAccount::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'gl_account_id', 'is_default']);
    }

    public function create(array $data): BankAccount
    {
        $this->assertGlAccountIsValid((int) $data['gl_account_id']);

        return DB::transaction(function () use ($data) {
            $account = BankAccount::query()->create([
                'bank_id' => $data['bank_id'],
                'gl_account_id' => $data['gl_account_id'],
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'account_number' => $data['account_number'],
                'iban' => $data['iban'] ?? null,
                'swift_code' => $data['swift_code'] ?? null,
                'account_type' => $data['account_type'] ?? 'current',
                'currency_code' => strtoupper($data['currency_code']),
                'opening_balance' => $data['opening_balance'] ?? 0,
                'opening_balance_date' => $data['opening_balance_date'] ?? null,
                'status' => $data['status'] ?? RecordStatus::Active,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if (! empty($data['is_default'])) {
                $this->makeDefault($account);
            }

            return $account->fresh();
        });
    }

    public function update(BankAccount $account, array $data): BankAccount
    {
        if (! empty($data['gl_account_id']) && (int) $data['gl_account_id'] !== $account->gl_account_id) {
            $this->assertGlAccountIsValid((int) $data['gl_account_id']);
            if ($this->currentBalance($account) !== '0.0000') {
                throw BusinessRuleException::make(
                    'Cannot re-map the GL account of a bank account that has activity.'
                );
            }
        }

        return DB::transaction(function () use ($account, $data) {
            $account->fill([
                'bank_id' => $data['bank_id'] ?? $account->bank_id,
                'gl_account_id' => $data['gl_account_id'] ?? $account->gl_account_id,
                'code' => isset($data['code']) ? strtoupper($data['code']) : $account->code,
                'name' => $data['name'] ?? $account->name,
                'account_number' => $data['account_number'] ?? $account->account_number,
                'iban' => $data['iban'] ?? $account->iban,
                'swift_code' => $data['swift_code'] ?? $account->swift_code,
                'account_type' => $data['account_type'] ?? $account->account_type,
                'currency_code' => isset($data['currency_code']) ? strtoupper($data['currency_code']) : $account->currency_code,
                'opening_balance' => $data['opening_balance'] ?? $account->opening_balance,
                'opening_balance_date' => $data['opening_balance_date'] ?? $account->opening_balance_date,
                'updated_by' => Auth::id(),
            ])->save();

            if (array_key_exists('is_default', $data) && $data['is_default']) {
                $this->makeDefault($account);
            }

            return $account->fresh();
        });
    }

    public function delete(BankAccount $account): void
    {
        if ($account->is_default) {
            throw BusinessRuleException::make('Cannot delete the default bank account.');
        }
        if ($this->currentBalance($account) !== '0.0000') {
            throw BusinessRuleException::make(
                'Cannot delete a bank account with activity. Deactivate it instead.'
            );
        }

        DB::transaction(fn () => $account->delete());
    }

    public function changeStatus(BankAccount $account, RecordStatus $status): BankAccount
    {
        if (! $account->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition from {$account->status->value} to {$status->value}."
            );
        }
        if ($status !== RecordStatus::Active && $account->is_default) {
            throw BusinessRuleException::make('Cannot deactivate the default bank account.');
        }

        $account->forceFill(['status' => $status, 'updated_by' => Auth::id()])->save();

        return $account->fresh();
    }

    public function makeDefault(BankAccount $account): BankAccount
    {
        if ($account->status !== RecordStatus::Active) {
            throw BusinessRuleException::make('Only active bank accounts can be default.');
        }

        return DB::transaction(function () use ($account) {
            BankAccount::query()
                ->where('is_default', true)
                ->where('id', '!=', $account->id)
                ->update(['is_default' => false]);
            $account->forceFill(['is_default' => true])->save();

            return $account->fresh();
        });
    }

    public function currentBalance(BankAccount $account): string
    {
        return $this->ledger->balance(new LedgerQuery(accountId: $account->gl_account_id));
    }

    // ─── Guards ──────────────────────────────────────────────────────

    private function assertGlAccountIsValid(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        if (! $account->is_postable) {
            throw BusinessRuleException::make(
                "GL account {$account->code} is not postable."
            );
        }
        if ($account->type->value !== 'asset') {
            throw BusinessRuleException::make(
                'A bank account must be linked to an Asset GL account.'
            );
        }
    }
}
