<?php

namespace App\Services\MasterData;

use App\Contracts\SequenceGenerator;
use App\Contracts\VendorManager;
use App\Enums\VendorStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class VendorService implements VendorManager
{
    public function __construct(private readonly SequenceGenerator $sequences) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Vendor::query()
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->withCount('addresses')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(): Collection
    {
        return Vendor::query()->active()->orderBy('name')->get(['id', 'code', 'name']);
    }

     public function allSupplier(): Collection
    {
        return Vendor::query()->where('vendor_type','supplier')->active()->orderBy('name')->get(['id', 'code', 'name']);
    }

    public function create(array $data): Vendor
    {
        return DB::transaction(function () use ($data) {
            $vendor = Vendor::query()->create([
                'code' => $this->nextAvailableCode(),
                'name' => $data['name'],
                'contact_person' => $data['contact_person'] ?? $data['name'],
                'legal_name' => $data['legal_name'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'status' => $data['status'] ?? VendorStatus::Pending,
                'category_id' => $data['category_id'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_branch' => $data['bank_branch'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_iban' => $data['bank_iban'] ?? null,
                'credit_days' => $data['credit_days'] ?? 0,
                'payment_term_id' => $data['payment_term_id'] ?? null,
                'default_tax_rate_id' => $data['default_tax_rate_id'] ?? null,
                'ap_account_id' => $data['ap_account_id'] ?? null,
                'is_tax_exempt' => $data['is_tax_exempt'] ?? false,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if (! empty($data['addresses'])) {
                $vendor->syncAddresses($data['addresses']);
            }

            return $vendor->fresh(['addresses']);
        });
    }

    public function update(Vendor $vendor, array $data): Vendor
    {
        return DB::transaction(function () use ($vendor, $data) {
            $status = array_key_exists('status', $data)
                ? VendorStatus::from($data['status'])
                : $vendor->status;

            if ($status !== $vendor->status && ! $vendor->status->canTransitionTo($status)) {
                throw BusinessRuleException::make(
                    "Cannot transition vendor status from {$vendor->status->value} to {$status->value}."
                );
            }

            $vendor->fill([
                'name' => $data['name'] ?? $vendor->name,
                'status' => $status,
                'contact_person' => $data['contact_person'] ?? $vendor->contact_person,
                'legal_name' => $data['legal_name'] ?? $vendor->legal_name,
                'tax_number' => $data['tax_number'] ?? $vendor->tax_number,
                'registration_number' => $data['registration_number'] ?? $vendor->registration_number,
                'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $vendor->category_id,
                'email' => $data['email'] ?? $vendor->email,
                'phone' => $data['phone'] ?? $vendor->phone,
                'bank_name' => $data['bank_name'] ?? $vendor->bank_name,
                'bank_branch' => $data['bank_branch'] ?? $vendor->bank_branch,
                'bank_account_number' => $data['bank_account_number'] ?? $vendor->bank_account_number,
                'bank_iban' => $data['bank_iban'] ?? $vendor->bank_iban,
                'credit_days' => $data['credit_days'] ?? $vendor->credit_days,
                'payment_term_id' => array_key_exists('payment_term_id', $data) ? $data['payment_term_id'] : $vendor->payment_term_id,
                'default_tax_rate_id' => array_key_exists('default_tax_rate_id', $data) ? $data['default_tax_rate_id'] : $vendor->default_tax_rate_id,
                'ap_account_id' => array_key_exists('ap_account_id', $data) ? $data['ap_account_id'] : $vendor->ap_account_id,
                'is_tax_exempt' => $data['is_tax_exempt'] ?? $vendor->is_tax_exempt,
                'notes' => $data['notes'] ?? $vendor->notes,
                'updated_by' => Auth::id(),
            ])->save();

            if (array_key_exists('addresses', $data)) {
                $vendor->syncAddresses($data['addresses'] ?? []);
            }

            return $vendor->fresh(['addresses']);
        });
    }

    public function delete(Vendor $vendor): void
    {
        if ($vendor->status !== VendorStatus::Pending) {
            throw BusinessRuleException::make(
                'Only pending vendors can be deleted. Suspend or deactivate active vendors instead.'
            );
        }

        DB::transaction(fn () => $vendor->delete());
    }

    public function changeStatus(Vendor $vendor, VendorStatus $status): Vendor
    {
        if (! $vendor->status->canTransitionTo($status)) {
            throw BusinessRuleException::make(
                "Cannot transition vendor status from {$vendor->status->value} to {$status->value}."
            );
        }

        $vendor->forceFill([
            'status' => $status,
            'updated_by' => Auth::id(),
        ])->save();

        return $vendor->fresh();
    }

    public function nextCode(): string
    {
        return $this->sequences->next('vendor', (int) now()->format('Y'));
    }

    private function nextAvailableCode(): string
    {
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $code = $this->nextCode();

            if (! Vendor::withTrashed()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw BusinessRuleException::make('Unable to allocate a unique vendor code.');
    }

    public function allTransportProviders(): Collection
    {
        return Vendor::query()
            ->transportProviders()
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }
}
