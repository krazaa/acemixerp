<?php

namespace Modules\Procurement\Http\Requests\Rfqs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Procurement\Models\RequestForQuotation;

class StoreRfqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', RequestForQuotation::class);
    }

    public function rules(): array
    {
        return [
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
            'currency_code' => ['required', 'string', 'size:3'],
            'purpose' => ['required', 'string', 'max:500'],
            'terms' => ['nullable', 'string', 'max:5000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')->whereNull('deleted_at')],
            'lines.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.specification' => ['nullable', 'string', 'max:500'],
            'lines.*.purchase_requisition_line_id' => ['nullable', 'integer', 'exists:purchase_requisition_lines,id'],

            'vendor_ids' => ['required', 'array', 'min:1'],
            'vendor_ids.*' => ['integer', 'exists:vendors,id'],

            'source_requisition_ids' => ['nullable', 'array'],
            'source_requisition_ids.*' => ['integer', 'exists:purchase_requisitions,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency_code')) {
            $this->merge(['currency_code' => strtoupper(trim((string) $this->input('currency_code')))]);
        }
    }
}
