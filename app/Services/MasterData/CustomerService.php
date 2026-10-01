<?php

namespace App\Services\MasterData;

use App\Contracts\CustomerManager;
use App\Contracts\SequenceGenerator;
use App\Enums\CustomerStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class CustomerService implements CustomerManager
{
    public function __construct(private readonly SequenceGenerator $sequences) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Customer::query()
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->withCount('addresses')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(): Collection
    {
        return Customer::query()->active()->orderBy('name')->get(['id', 'code', 'name']);
    }

    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::query()->create([
                'code' => $data['code'] ?? $this->nextCode(),
                'name' => $data['name'],
                'c_person' => $data['c_person'] ?? $data['name'],
                'legal_name' => $data['legal_name'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'status' => $data['status'] ?? CustomerStatus::Prospect,
                'category_id' => $data['category_id'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'cp_phone' => $data['cp_phone'] ?? null,
                'credit_limit' => $data['credit_limit'] ?? 0,
                'credit_days' => $data['credit_days'] ?? 0,
                'payment_term_id' => $data['payment_term_id'] ?? null,
                'default_tax_rate_id' => $data['default_tax_rate_id'] ?? null,
                'ar_account_id' => $data['ar_account_id'] ?? null,
                'is_tax_exempt' => $data['is_tax_exempt'] ?? false,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if (! empty($data['addresses'])) {
                $customer->syncAddresses($data['addresses']);
            }

            return $customer->fresh(['addresses']);
        });
    }

    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->fill([
                'name' => $data['name'] ?? $customer->name,
                'legal_name' => $data['legal_name'] ?? $customer->legal_name,
                'c_person' => $data['c_person'] ?? $customer->c_person,
                'tax_number' => $data['tax_number'] ?? $customer->tax_number,
                'registration_number' => $data['registration_number'] ?? $customer->registration_number,
                'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $customer->category_id,
                'email' => $data['email'] ?? $customer->email,
                'phone' => $data['phone'] ?? $customer->phone,
                'cp_phone' => $data['cp_phone'] ?? $customer->cp_phone,
                'credit_limit' => $data['credit_limit'] ?? $customer->credit_limit,
                'credit_days' => $data['credit_days'] ?? $customer->credit_days,
                'payment_term_id' => array_key_exists('payment_term_id', $data) ? $data['payment_term_id'] : $customer->payment_term_id,
                'default_tax_rate_id' => array_key_exists('default_tax_rate_id', $data) ? $data['default_tax_rate_id'] : $customer->default_tax_rate_id,
                'ar_account_id' => array_key_exists('ar_account_id', $data) ? $data['ar_account_id'] : $customer->ar_account_id,
                'is_tax_exempt' => $data['is_tax_exempt'] ?? $customer->is_tax_exempt,
                'notes' => $data['notes'] ?? $customer->notes,
                'updated_by' => Auth::id(),
            ])->save();

            if (array_key_exists('addresses', $data)) {
                $customer->syncAddresses($data['addresses'] ?? []);
            }

            return $customer->fresh(['addresses']);
        });
    }

    public function delete(Customer $customer): void
    {
        if ($customer->status !== CustomerStatus::Prospect) {
            throw BusinessRuleException::make(
                'Only prospect customers can be deleted. Deactivate active customers instead.'
            );
        }

        DB::transaction(fn () => $customer->delete());
    }

    public function changeStatus(Customer $customer, CustomerStatus $status): Customer
    {
        if (! $customer->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition customer status from {$customer->status->value} to {$status->value}."
            );
        }

        $customer->forceFill([
            'status' => $status,
            'updated_by' => Auth::id(),
        ])->save();

        return $customer->fresh();
    }

    public function nextCode(): string
    {
        return $this->sequences->next('customer', (int) now()->format('Y'));
    }
}
