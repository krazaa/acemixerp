<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Requests\Rfqs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRfqFromRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('convertToRfq', $this->route('purchaseRequisition')) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'vendor_ids' => ['required', 'array', 'min:1'],
            'vendor_ids.*' => ['required', 'integer', 'distinct', Rule::exists('vendors', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'terms' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
