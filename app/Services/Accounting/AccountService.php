<?php

namespace App\Services\Accounting;

use App\Contracts\AccountManager;
use App\Enums\AccountType;
use App\Enums\RecordStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Account;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class AccountService implements AccountManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Account::query()
            ->with(['parent:id,code,name'])
            ->search($filters['search'] ?? null)
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['postable'] ?? null, fn ($q) => $q->where('is_postable', (bool) $filters['postable']))
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allPostable(): Collection
    {
        return Account::query()
            ->postable()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'normal_balance']);
    }

    public function tree(): Collection
    {
        $all = Account::query()->orderBy('code')->get();

        return $this->buildTree($all);
    }

    public function create(array $data): Account
    {
        return DB::transaction(function () use ($data) {
            $type = AccountType::from($data['type']);

            $account = Account::query()->create([
                'code' => $this->normalizeCode($data['code']),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $type,
                'normal_balance' => $data['normal_balance'] ?? $type->normalBalance(),
                'parent_id' => $data['parent_id'] ?? null,
                'is_postable' => $data['is_postable'] ?? true,
                'is_cash' => $data['is_cash'] ?? false,
                'is_bank' => $data['is_bank'] ?? false,
                'requires_cost_center' => $data['requires_cost_center'] ?? false,
                'requires_department' => $data['requires_department'] ?? false,
                'requires_party' => $data['requires_party'] ?? false,
                'currency_code' => $data['currency_code'] ?? null,
                'status' => $data['status'] ?? RecordStatus::Active,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->validateParentType($account, $data['parent_id'] ?? null);

            return $account->fresh();
        });
    }

    public function update(Account $account, array $data): Account
    {
        return DB::transaction(function () use ($account, $data) {
            $locked = $account->isLocked();

            // Structural fields are immutable once postings exist (§57).
            if ($locked) {
                foreach (['code', 'type', 'normal_balance', 'is_postable'] as $field) {
                    if (array_key_exists($field, $data)
                        && (string) $data[$field] !== (string) $account->{$field}?->value
                        && (string) $data[$field] !== (string) $account->{$field}) {
                        throw BusinessRuleException::make(
                            "Field [{$field}] cannot be changed once the account has postings."
                        );
                    }
                }
            }

            $account->fill([
                'code' => isset($data['code']) ? $this->normalizeCode($data['code']) : $account->code,
                'name' => $data['name'] ?? $account->name,
                'description' => $data['description'] ?? $account->description,
                'type' => $data['type'] ?? $account->type,
                'normal_balance' => $data['normal_balance'] ?? $account->normal_balance,
                'parent_id' => array_key_exists('parent_id', $data) ? $data['parent_id'] : $account->parent_id,
                'is_postable' => $data['is_postable'] ?? $account->is_postable,
                'is_cash' => $data['is_cash'] ?? $account->is_cash,
                'is_bank' => $data['is_bank'] ?? $account->is_bank,
                'requires_cost_center' => $data['requires_cost_center'] ?? $account->requires_cost_center,
                'requires_department' => $data['requires_department'] ?? $account->requires_department,
                'requires_party' => $data['requires_party'] ?? $account->requires_party,
                'currency_code' => $data['currency_code'] ?? $account->currency_code,
                'updated_by' => Auth::id(),
            ])->save();

            if (array_key_exists('parent_id', $data)) {
                $this->validateParentType($account, $data['parent_id']);
                $this->guardCycle($account, $data['parent_id']);
            }

            return $account->fresh();
        });
    }

    public function delete(Account $account): void
    {
        if ($account->hasPostings()) {
            throw BusinessRuleException::make(
                'Account has journal postings and cannot be deleted. Deactivate it instead.'
            );
        }

        if ($account->children()->exists()) {
            throw BusinessRuleException::make(
                'Account has child accounts. Delete or re-parent them first.'
            );
        }

        if ($account->systemRole()->exists()) {
            throw BusinessRuleException::make(
                'Account is mapped as a system account. Unmap it first.'
            );
        }

        DB::transaction(fn () => $account->delete());
    }

    public function changeStatus(Account $account, RecordStatus $status): Account
    {
        if (! $account->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition from {$account->status->value} to {$status->value}."
            );
        }

        $account->forceFill([
            'status' => $status,
            'updated_by' => Auth::id(),
        ])->save();

        return $account->fresh();
    }

    public function findByCode(string $code): ?Account
    {
        return Account::query()->where('code', $this->normalizeCode($code))->first();
    }

    public function nextCodeFor(AccountType $type): string
    {
        $prefix = match ($type) {
            AccountType::Asset => '1',
            AccountType::Liability => '2',
            AccountType::Equity => '3',
            AccountType::Revenue => '4',
            AccountType::Cogs => '5',
            AccountType::Expense => '6',
        };

        $max = Account::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->whereRaw('LENGTH(code) = 4')
            ->max('code');

        $next = $max ? ((int) $max + 1) : (int) ($prefix.'000');

        return (string) $next;
    }

    private function normalizeCode(string $code): string
    {
        return trim($code);
    }

    private function validateParentType(Account $account, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = Account::query()->findOrFail($parentId);

        if ($parent->type !== $account->type) {
            throw BusinessRuleException::make(
                "A {$account->type->label()} account cannot be a child of a {$parent->type->label()} account."
            );
        }

        if ($parent->is_postable) {
            // Auto-flip the parent to non-postable only if it has no postings.
            if ($parent->isLocked()) {
                throw BusinessRuleException::make(
                    'Parent account has postings and cannot become a header account. '.
                    'Choose a different parent or create a header account first.'
                );
            }
            $parent->forceFill(['is_postable' => false])->save();
        }
    }

    private function guardCycle(Account $account, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }
        if ($parentId === $account->id) {
            throw BusinessRuleException::make('An account cannot be its own parent.');
        }
        if (in_array($parentId, $account->descendantIds(), true)) {
            throw BusinessRuleException::make(
                'Cannot assign a descendant account as parent (would create a cycle).'
            );
        }
    }

    private function buildTree(Collection $all, ?int $parentId = null): Collection
    {
        return $all
            ->where('parent_id', $parentId)
            ->map(function (Account $a) use ($all) {
                $a->setRelation('children', $this->buildTree($all, $a->id));

                return $a;
            })
            ->values();
    }
}
