<?php

namespace Modules\Procurement\Http\Requests\PurchaseRequisitions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('purchaseRequisition'));
    }

    public function rules(): array
    {
        return [
            'requested_date' => ['sometimes', 'date'],
            'required_date' => ['nullable', 'date', 'after_or_equal:requested_date'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'warehouse_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'requested_by' => ['nullable', 'integer', 'exists:users,id'],
            'purpose' => ['sometimes', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')->whereNull('deleted_at')],
            'lines.*.origin_id' => ['nullable', 'integer', Rule::exists('origins', 'id')->whereNull('deleted_at')],
            'lines.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.estimated_unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.required_date' => ['nullable', 'date'],
            'lines.*.specification' => ['nullable', 'string', 'max:500'],
        ];
    }
}
