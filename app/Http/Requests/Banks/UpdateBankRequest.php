<?php

namespace App\Http\Requests\Banks;

use App\Enums\RecordStatus;
use App\Models\Bank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Bank::class);
    }

    public function rules(): array
    {
        $id = $this->route('bank')->id;

        return [
            'code' => [
                'sometimes', 'string', 'max:32', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('banks', 'code')->ignore($id),
            ],
            'name' => ['required', 'string', 'max:192'],
            'short_name' => ['nullable', 'string', 'max:64'],

            'account_number' => ['nullable', 'string', 'max:16',  Rule::unique('banks', 'account_number')
                ->ignore($this->route('bank')->id)],
            'iban' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(RecordStatus::class)],

            'bank_contacts' => ['nullable', 'array', 'max:20'],
            'bank_contacts.*.name' => ['nullable', 'string', 'max:128'],
            'bank_contacts.*.role' => ['nullable', 'string', 'max:64'],
            'bank_contacts.*.email' => ['nullable', 'email', 'max:255'],
            'bank_contacts.*.phone' => ['nullable', 'string', 'max:32'],
            'bank_contacts.*.notes' => ['nullable', 'string', 'max:500'],

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
            'addresses.*.state' => ['nullable', 'string', 'max:100'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.*.country' => ['required_with:addresses', 'string', 'size:2'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
        if (! $this->has('country')) {
            $this->merge(['country' => 'US']);
        }
        if (! $this->has('status')) {
            $this->merge(['status' => RecordStatus::Active->value]);
        }
    }
}
