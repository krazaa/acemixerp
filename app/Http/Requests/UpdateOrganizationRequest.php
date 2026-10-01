<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', Organization::current());
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:64'],
            'registration_number' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'website' => ['nullable', 'url', 'max:255'],
            'logo_path' => ['nullable', 'string'],

            'currency_code' => ['required', 'string', 'size:3', Rule::in($this->supportedCurrencies())],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'currency_decimals' => ['required', 'integer', 'min:0', 'max:4'],

            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'date_format' => ['required', 'string', Rule::in(['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-M-Y'])],
            'fiscal_year_start_month' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])$/'],

            'inventory_valuation_method' => ['required', Rule::in(['FIFO', 'WEIGHTED_AVERAGE'])],
            'allow_negative_stock' => ['sometimes', 'boolean'],
            'require_approval_for_journal' => ['sometimes', 'boolean'],

            'logo' => [
                'sometimes', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', // 2MB
                'dimensions:max_width=2000,max_height=2000',
            ],

            'meta' => ['sometimes', 'array'],

            'addresses' => ['nullable', 'array'],
            'addresses.*' => ['array:id,type,is_primary,label,contact_name,contact_email,contact_phone,address_line1,address_line2,city,state,postal_code,country'],
            'addresses.*.id' => [
                'nullable', 'integer', 'distinct',
                Rule::exists('addresses', 'id')
                    ->where('addressable_type', (new Organization)->getMorphClass())
                    ->where('addressable_id', Organization::SINGLETON_ID)
                    ->whereNull('deleted_at'),
            ],
            'addresses.*.type' => ['required_with:addresses', Rule::in(['billing', 'shipping', 'office', 'other'])],
            'addresses.*.is_primary' => ['sometimes', 'boolean'],
            'addresses.*.label' => ['nullable', 'string', 'max:64'],
            'addresses.*.contact_name' => ['nullable', 'string', 'max:128'],
            'addresses.*.contact_email' => ['nullable', 'email', 'max:255'],
            'addresses.*.contact_phone' => ['nullable', 'string', 'max:32'],
            'addresses.*.address_line1' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.address_line2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:100'],
            'addresses.*.state' => ['nullable', 'string', 'max:100'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.*.country' => ['nullable', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('addresses_present') && ! $this->has('addresses')) {
            $this->merge(['addresses' => []]);
        }

        $this->merge([
            'allow_negative_stock' => $this->boolean('allow_negative_stock'),
            'require_approval_for_journal' => $this->boolean('require_approval_for_journal'),
        ]);
    }

    /** @return array<int, string> */
    private function supportedCurrencies(): array
    {
        // Small curated set; replace with a DB-backed table in Phase 2 if needed.
        return ['USD', 'EUR', 'GBP', 'PKR', 'AED', 'SAR', 'JPY', 'CNY', 'AUD', 'CAD'];
    }
}
