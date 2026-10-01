<?php

namespace Modules\Procurement\Http\Requests\VendorQuotations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('rfq'));
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('lines'))) {
            $this->merge(['lines' => array_filter($this->input('lines'), static function (mixed $line): bool {
                return ! is_array($line) || ! array_key_exists('unit_price', $line)
                    || ($line['unit_price'] !== null && $line['unit_price'] !== '');
            })]);
        }
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'reference' => ['nullable', 'string', 'max:64'],
            'quoted_at' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quoted_at'],
            'currency_code' => ['required', 'string', 'size:3'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.rfq_line_id' => ['required', 'integer', 'distinct', Rule::exists('rfq_lines', 'id')->where('request_for_quotation_id', $this->route('rfq')->id)],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.wht_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
