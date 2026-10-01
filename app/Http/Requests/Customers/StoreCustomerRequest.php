<?php

declare(strict_types=1);

namespace App\Http\Requests\Customers;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Customer::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:192'],
            'legal_name' => ['nullable', 'string', 'max:192'],
            'c_person' => ['nullable', 'string', 'max:192'],
            'tax_number' => ['nullable', 'string', 'max:64'],
            'registration_number' => ['nullable', 'string', 'max:64'],
            'status' => ['required', Rule::enum(CustomerStatus::class)],
            'category_id' => ['nullable', 'integer'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'cp_phone' => ['nullable', 'string', 'max:32'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'payment_term_id' => ['nullable', 'integer'],
            'default_tax_rate_id' => ['nullable', 'integer'],
            'ar_account_id' => ['nullable', 'integer'],
            'is_tax_exempt' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],

            // Addresses (nested)
            'addresses' => ['nullable', 'array'],
            'addresses.*.id' => ['nullable', 'integer'],
            'addresses.*.type' => ['required_with:addresses', Rule::in(['billing', 'shipping', 'office', 'other'])],
            'addresses.*.is_primary' => ['sometimes', 'boolean'],
            'addresses.*.label' => ['nullable', 'string', 'max:64'],
            'addresses.*.contact_name' => ['nullable', 'string', 'max:128'],
            'addresses.*.contact_email' => ['nullable', 'email', 'max:255'],
            'addresses.*.contact_phone' => ['nullable', 'string', 'max:32'],
            'addresses.*.address_line1' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.address_line2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:100'],

        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_tax_exempt' => $this->boolean('is_tax_exempt'),
            'credit_limit' => $this->input('credit_limit') ?: 0,
            'credit_days' => $this->input('credit_days') ?: 0,
        ]);
    }
}
