<?php

namespace App\Services\MasterData;

use App\Contracts\BankManager;
use App\Enums\RecordStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Bank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class BankService implements BankManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Bank::query()
            ->withCount('addresses')
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(): Collection
    {
        return Bank::query()->active()->orderBy('name')->get(['id', 'code', 'name', 'short_name']);
    }

    public function create(array $data): Bank
    {
        return DB::transaction(function () use ($data) {
            $bank = Bank::query()->create([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'bank_contacts' => Bank::normalizeContacts($data['bank_contacts'] ?? []),
                'short_name' => $data['short_name'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'iban' => $data['iban'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? RecordStatus::Active,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if (! empty($data['addresses'])) {
                $bank->syncAddresses($data['addresses']);
            }

            return $bank->fresh(['addresses']);
        });
    }

    public function update(Bank $bank, array $data): Bank
    {
        return DB::transaction(function () use ($bank, $data) {
            $bank->fill([
                'code' => isset($data['code']) ? strtoupper($data['code']) : $bank->code,
                'name' => $data['name'] ?? $bank->name,
                'short_name' => $data['short_name'] ?? $bank->short_name,
                'iban' => $data['iban'] ?? $bank->iban,
                'account_number' => $data['account_number'] ?? $bank->account_number,
                'notes' => $data['notes'] ?? $bank->notes,
                'bank_contacts' => array_key_exists('bank_contacts', $data)
                    ? Bank::normalizeContacts($data['bank_contacts'] ?? [])
                    : $bank->bank_contacts,
                'updated_by' => Auth::id(),
            ])->save();

            if (array_key_exists('addresses', $data)) {
                $bank->syncAddresses($data['addresses'] ?? []);
            }

            return $bank->fresh(['addresses']);
        });
    }

    public function delete(Bank $bank): void
    {
        // Phase 3 will add: block if bank accounts exist.
        DB::transaction(fn () => $bank->delete());
    }

    public function changeStatus(Bank $bank, RecordStatus $status): Bank
    {
        if (! $bank->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition bank status from {$bank->status->value} to {$status->value}."
            );
        }

        $bank->forceFill([
            'status' => $status,
            'updated_by' => Auth::id(),
        ])->save();

        return $bank->fresh();
    }
}
