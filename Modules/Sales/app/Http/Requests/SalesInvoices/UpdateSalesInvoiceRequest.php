<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests\SalesInvoices;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Sales\Models\SalesInvoice;

class UpdateSalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', SalesInvoice::class);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'sales_order_id' => ['nullable', 'integer', 'exists:sales_orders,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'payment_term_id' => ['nullable', 'integer', 'exists:payment_terms,id'],
            'currency_code' => ['required', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:5000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.wht_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.sales_order_line_id' => ['nullable', 'integer', 'exists:sales_order_lines,id'],
            'lines.*.delivery_line_id' => ['nullable', 'integer', 'exists:delivery_lines,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency_code')) {
            $this->merge(['currency_code' => strtoupper(trim((string) $this->input('currency_code')))]);
        }
    }
}
