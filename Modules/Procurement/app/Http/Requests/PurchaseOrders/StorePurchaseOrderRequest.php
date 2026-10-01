<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Requests\PurchaseOrders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Procurement\Models\PurchaseOrder;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PurchaseOrder::class);
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'currency_code' => ['required', 'string', 'size:3'],
            'payment_term_id' => ['nullable', 'integer', 'exists:payment_terms,id'],
            'reference' => ['nullable', 'string', 'max:64'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:5000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')->whereNull('deleted_at')],
            'lines.*.origin_id' => ['nullable', 'integer', Rule::exists('origins', 'id')->whereNull('deleted_at')],
            'lines.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.wht_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.required_date' => ['nullable', 'date'],
            'lines.*.specification' => ['nullable', 'string', 'max:500'],
            'lines.*.rfq_line_id' => ['nullable', 'integer', 'exists:rfq_lines,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency_code')) {
            $this->merge(['currency_code' => strtoupper(trim((string) $this->input('currency_code')))]);
        }
    }
}
