<?php

namespace App\Services\Organization;

use App\Contracts\OrganizationUpdater;
use App\Data\OrganizationData;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\ConcurrencyException;
use App\Models\FinancialYear;
use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class DatabaseOrganizationUpdater implements OrganizationUpdater
{
    private const LOGO_DISK = 'local';

    private const LOGO_DIR = 'organizations/logos';

    public function update(OrganizationData $data, ?UploadedFile $logo = null): Organization
    {
        return DB::transaction(function () use ($data, $logo) {

            /** @var Organization $org */
            $org = Organization::query()
                ->whereKey(Organization::SINGLETON_ID)
                ->lockForUpdate()
                ->first();

            if (! $org) {
                throw new ConcurrencyException('Organization record is missing.');
            }

            $this->guardLockedFields($org, $data);

            $payload = $data->toArray();

            if ($data->addresses !== null) {
                $addresses = array_map(
                    fn (array $address): array => array_replace($address, ['country' => $address['country'] ?? $data->country]),
                    array_values($data->addresses),
                );
                $org->syncAddresses($addresses);

            }

            if ($logo !== null) {
                $payload['logo_path'] = $this->storeLogo($org, $logo);
            }

            $org->fill($payload)->save();

            Organization::flushCache();

            return $org->fresh();
        }, attempts: 3);
    }

    /**
     * Enforces §23 / §25: valuation method & currency become immutable once
     * any transaction exists in a closed or open financial year.
     */
    private function guardLockedFields(Organization $org, OrganizationData $incoming): void
    {
        $hasPostedActivity = FinancialYear::query()
            ->whereIn('status', ['open', 'closing', 'closed'])
            ->exists();

        if (! $hasPostedActivity) {
            return;
        }

        foreach (Organization::lockedAfterPosting() as $field) {
            $incomingValue = match ($field) {
                'currency_code' => $incoming->currencyCode,
                'currency_decimals' => $incoming->currencyDecimals,
                'inventory_valuation_method' => $incoming->inventoryValuationMethod,
            };

            if ((string) $org->{$field} !== (string) $incomingValue) {
                throw BusinessRuleException::make(
                    "Field [{$field}] cannot be changed after financial activity exists. ".
                    'Create a new fiscal configuration instead.'
                );
            }
        }
    }

    private function storeLogo(Organization $org, UploadedFile $logo): string
    {
        $ext = strtolower($logo->getClientOriginalExtension() ?: 'png');
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'png';

        $filename = Str::uuid()->toString().'.'.$ext;
        $path = self::LOGO_DIR.'/'.$filename;

        Storage::disk(self::LOGO_DISK)->putFileAs(
            self::LOGO_DIR,
            $logo,
            $filename,
            ['visibility' => 'private'],
        );

        // Remove previous logo (after successful store).
        if ($org->logo_path && Storage::disk(self::LOGO_DISK)->exists($org->logo_path)) {
            Storage::disk(self::LOGO_DISK)->delete($org->logo_path);
        }

        return $path;
    }
}
