<?php

declare(strict_types=1);

namespace App\Http\Requests\Warehouses;

use App\Enums\RecordStatus;
use App\Enums\WarehouseType;
use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Warehouse::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('warehouses', 'code')],
            'name' => ['required', 'string', 'max:128', Rule::unique('warehouses', 'name')],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::enum(WarehouseType::class)],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],

            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],

            'is_default' => ['sometimes', 'boolean'],
            'allow_negative_stock' => ['sometimes', 'boolean'],
            'is_pickable' => ['sometimes', 'boolean'],

            'status' => ['required', Rule::enum(RecordStatus::class)],

            // Addresses (nested) — same schema as customers/vendors
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
        if (! $this->has('type')) {
            $this->merge(['type' => WarehouseType::Main->value]);
        }
        if (! $this->has('status')) {
            $this->merge(['status' => RecordStatus::Active->value]);
        }
    }
}
