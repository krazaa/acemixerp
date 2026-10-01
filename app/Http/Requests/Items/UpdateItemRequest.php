<?php

namespace App\Http\Requests\Items;

use App\Enums\ItemType;
use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('item'));
    }

    public function rules(): array
    {
        $id = $this->route('item')->id;

        return [
            'code' => ['nullable', 'string', 'max:32', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('items', 'code')->ignore($id)],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('items', 'sku')->ignore($id)],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('items', 'barcode')->ignore($id)],
            'name' => ['required', 'string', 'max:192'],
            'description' => ['nullable', 'string', 'max:2000'],
            'item_type' => ['required', Rule::enum(ItemType::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],

            'tax_rate_id' => ['nullable', 'integer'],
            'is_tax_exempt' => ['sometimes', 'boolean'],

            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'minimum_selling_price' => ['nullable', 'numeric', 'min:0'],

            'track_inventory' => ['sometimes', 'boolean'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'maximum_stock' => ['nullable', 'numeric', 'min:0', 'gte:minimum_stock'],
            'allow_negative_stock' => ['sometimes', 'boolean'],

            'track_batch' => ['sometimes', 'boolean'],
            'track_serial' => ['sometimes', 'boolean'],
            'track_expiry' => ['sometimes', 'boolean'],

            'is_sellable' => ['sometimes', 'boolean'],
            'is_purchasable' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::enum(RecordStatus::class)],

            'image' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $defaults = [
            'cost_price' => 0,
            'selling_price' => 0,
            'reorder_level' => 0,
            'minimum_stock' => 0,
            'is_tax_exempt' => false,
            'track_inventory' => true,
            'allow_negative_stock' => false,
            'track_batch' => false,
            'track_serial' => false,
            'track_expiry' => false,
            'is_sellable' => true,
            'is_purchasable' => true,
        ];

        $merge = [];
        foreach ($defaults as $key => $val) {
            if (! $this->has($key)) {
                $merge[$key] = $val;
            }
        }
        if (! $this->has('item_type')) {
            $merge['item_type'] = ItemType::Stock->value;
        }
        if (! $this->has('status')) {
            $merge['status'] = RecordStatus::Active->value;
        }
        if ($this->has('code')) {
            $merge['code'] = strtoupper(trim((string) $this->input('code')));
        }

        $this->merge($merge);
    }
}
