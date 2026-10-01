<?php

declare(strict_types=1);

namespace App\Http\Requests\TaxRates;

use App\Enums\RecordStatus;
use App\Enums\TaxRateComponent;
use App\Enums\TaxRateType;
use App\Models\TaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TaxRate::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('tax_rates', 'code')],
            'name' => ['required', 'string', 'max:128'],
            'type' => ['required', Rule::enum(TaxRateType::class)],
            'component' => ['required', Rule::enum(TaxRateComponent::class)],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_default' => ['sometimes', 'boolean'],
            'is_compound' => ['sometimes', 'boolean'],
            'is_recoverable' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
        $defaults = [
            'type' => TaxRateType::Standard->value,
            'component' => TaxRateComponent::Output->value,
            'rate' => 0,
            'effective_from' => now()->toDateString(),
            'is_default' => false,
            'is_compound' => false,
            'is_recoverable' => true,
            'status' => RecordStatus::Active->value,
        ];
        $merge = [];
        foreach ($defaults as $k => $v) {
            if (! $this->has($k)) {
                $merge[$k] = $v;
            }
        }
        if ($merge) {
            $this->merge($merge);
        }
    }
}
