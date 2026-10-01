<?php

namespace App\Http\Requests\PaymentTerms;

use App\Enums\PaymentTermType;
use App\Enums\RecordStatus;
use App\Models\PaymentTerm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PaymentTerm::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('payment_terms', 'code')],
            'name' => ['required', 'string', 'max:128'],
            'type' => ['required', Rule::enum(PaymentTermType::class)],
            'days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:31', 'required_if:type,day_of_month'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_default' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
        if (! $this->has('type')) {
            $this->merge(['type' => PaymentTermType::Net->value]);
        }
        if (! $this->has('days')) {
            $this->merge(['days' => 0]);
        }
        if (! $this->has('status')) {
            $this->merge(['status' => RecordStatus::Active->value]);
        }
    }
}
