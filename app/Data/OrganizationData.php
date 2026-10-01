<?php

namespace App\Data;

use App\Http\Requests\UpdateOrganizationRequest;

final readonly class OrganizationData
{
    public function __construct(
        public string $name,
        public ?string $legalName,
        public ?string $taxNumber,
        public ?string $registrationNumber,
        public ?string $email,
        public ?string $phone,
        public ?string $website,
        public string $country,
        public string $currencyCode,
        public string $currencySymbol,
        public int $currencyDecimals,
        public string $timezone,
        public string $dateFormat,
        public string $fiscalYearStartMonth,
        public string $inventoryValuationMethod,
        public bool $allowNegativeStock,
        public bool $requireApprovalForJournal,
        public ?array $meta = null,
        public ?array $addresses = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'legal_name' => $this->legalName,
            'tax_number' => $this->taxNumber,
            'registration_number' => $this->registrationNumber,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'country' => $this->country,
            'currency_code' => $this->currencyCode,
            'currency_symbol' => $this->currencySymbol,
            'currency_decimals' => $this->currencyDecimals,
            'timezone' => $this->timezone,
            'date_format' => $this->dateFormat,
            'fiscal_year_start_month' => $this->fiscalYearStartMonth,
            'inventory_valuation_method' => $this->inventoryValuationMethod,
            'allow_negative_stock' => $this->allowNegativeStock,
            'require_approval_for_journal' => $this->requireApprovalForJournal,
            'meta' => $this->meta,
        ];
    }

    public static function fromRequest(UpdateOrganizationRequest $r): self
    {
        return new self(
            name: (string) $r->string('name'),
            legalName: $r->input('legal_name'),
            taxNumber: $r->input('tax_number'),
            registrationNumber: $r->input('registration_number'),
            email: $r->input('email'),
            phone: $r->input('phone'),
            website: $r->input('website'),
            country: (string) ($r->input('country') ?? 'PK'),
            currencyCode: (string) $r->string('currency_code'),
            currencySymbol: (string) $r->string('currency_symbol'),
            currencyDecimals: (int) $r->integer('currency_decimals'),
            timezone: (string) $r->string('timezone'),
            dateFormat: (string) $r->string('date_format'),
            fiscalYearStartMonth: (string) $r->string('fiscal_year_start_month'),
            inventoryValuationMethod: (string) $r->string('inventory_valuation_method'),
            allowNegativeStock: $r->boolean('allow_negative_stock'),
            requireApprovalForJournal: $r->boolean('require_approval_for_journal'),
            meta: $r->input('meta'),
            addresses: $r->has('addresses') ? ($r->validated('addresses') ?? []) : null,
        );
    }
}
