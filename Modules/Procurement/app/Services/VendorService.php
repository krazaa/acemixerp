<?php

declare(strict_types=1);

namespace Modules\Procurement\Services;

use App\Contracts\SequenceGenerator;
use App\Enums\VendorStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Procurement\Contracts\VendorManager;

final class VendorService implements VendorManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Vendor::query()
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['category_id'] ?? null, fn ($q, $c) => $q->where('category_id', $c))
            ->when($filters['transport_only'] ?? false, fn ($q) => $q->where('is_transport_provider', true))
            ->withCount('addresses')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allActive(): Collection
    {
        return Vendor::query()
            ->where('status', VendorStatus::Active->value)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    public function allTransportProviders(): Collection
    {
        return Vendor::query()
            ->where('status', VendorStatus::Active->value)
            ->where('is_transport_provider', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    public function create(array $data): Vendor
    {
        return DB::transaction(function () use ($data) {
            $vendor = Vendor::query()->create([
                'code' => $data['code'] ?? $this->nextCode(),
                'name' => $data['name'],
                'legal_name' => $data['legal_name'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'status' => $data['status'] ?? VendorStatus::Pending,
                'category_id' => $data['category_id'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'website' => $data['website'] ?? null,
                'currency_code' => $data['currency_code'] ?? null,
                'credit_days' => $data['credit_days'] ?? 0,
                'payment_term_id' => $data['payment_term_id'] ?? null,
                'default_tax_rate_id' => $data['default_tax_rate_id'] ?? null,
                'ap_account_id' => $data['ap_account_id'] ?? null,
                'is_tax_exempt' => $data['is_tax_exempt'] ?? false,
                'is_transport_provider' => $data['is_transport_provider'] ?? false,
                'transport_service_area' => $data['transport_service_area'] ?? null,
                'transport_fleet_size' => $data['transport_fleet_size'] ?? null,
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
            $vendor->fill([
                'name' => $data['name'] ?? $vendor->name,
                'legal_name' => $data['legal_name'] ?? $vendor->legal_name,
                'tax_number' => $data['tax_number'] ?? $vendor->tax_number,
                'registration_number' => $data['registration_number'] ?? $vendor->registration_number,
                'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $vendor->category_id,
                'email' => $data['email'] ?? $vendor->email,
                'phone' => $data['phone'] ?? $vendor->phone,
                'website' => $data['website'] ?? $vendor->website,
                'currency_code' => $data['currency_code'] ?? $vendor->currency_code,
                'credit_days' => $data['credit_days'] ?? $vendor->credit_days,
                'payment_term_id' => array_key_exists('payment_term_id', $data) ? $data['payment_term_id'] : $vendor->payment_term_id,
                'default_tax_rate_id' => array_key_exists('default_tax_rate_id', $data) ? $data['default_tax_rate_id'] : $vendor->default_tax_rate_id,
                'ap_account_id' => array_key_exists('ap_account_id', $data) ? $data['ap_account_id'] : $vendor->ap_account_id,
                'is_tax_exempt' => $data['is_tax_exempt'] ?? $vendor->is_tax_exempt,
                'is_transport_provider' => $data['is_transport_provider'] ?? $vendor->is_transport_provider,
                'transport_service_area' => array_key_exists('transport_service_area', $data) ? $data['transport_service_area'] : $vendor->transport_service_area,
                'transport_fleet_size' => array_key_exists('transport_fleet_size', $data) ? $data['transport_fleet_size'] : $vendor->transport_fleet_size,
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
}
