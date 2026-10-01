<?php

namespace App\Models\Concerns;

use App\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

trait HasAddresses
{
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function billingAddress(): ?Address
    {
        return $this->addresses->firstWhere('type', 'billing')
            ?? $this->addresses->firstWhere('is_primary', true);
    }

    public function shippingAddress(): ?Address
    {
        return $this->addresses->firstWhere('type', 'shipping')
            ?? $this->billingAddress();
    }

    /**
     * Replace the full set of addresses in a single transaction.
     * Removed addresses are soft-deleted (§9 — no hard delete of referenced rows).
     *
     * @param  array<int, array<string, mixed>>  $addresses
     */
    public function syncAddresses(array $addresses): void
    {
        DB::transaction(function () use ($addresses) {
            $keepIds = [];

            foreach ($addresses as $data) {
                /** @var Address $address */
                $address = isset($data['id']) && $data['id']
                    ? $this->addresses()->whereKey($data['id'])->firstOrFail()
                    : $this->addresses()->make();

                $address->fill([
                    'type' => $data['type'] ?? 'billing',
                    'is_primary' => (bool) ($data['is_primary'] ?? false),
                    'label' => $data['label'] ?? null,
                    'contact_name' => $data['contact_name'] ?? null,
                    'contact_email' => $data['contact_email'] ?? null,
                    'contact_phone' => $data['contact_phone'] ?? null,
                    'address_line1' => $data['address_line1'],
                    'address_line2' => $data['address_line2'] ?? null,
                    'city' => $data['city'],
                    'state' => $data['state'] ?? null,
                    'postal_code' => $data['postal_code'] ?? null,
                    'country' => $data['country'] ?? 'US',
                ])->save();

                $keepIds[] = $address->id;
            }

            $this->addresses()
                ->when(! empty($keepIds), fn ($q) => $q->whereNotIn('id', $keepIds))
                ->delete();
        });
    }
}
