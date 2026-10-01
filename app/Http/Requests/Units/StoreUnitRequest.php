<?php

namespace App\Http\Requests\Units;

use App\Enums\RecordStatus;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Unit::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:16', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('units', 'code')],
            'name' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:500'],
            'quantity_precision' => ['required', 'integer', 'min:0', 'max:6'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
        if (! $this->has('quantity_precision')) {
            $this->merge(['quantity_precision' => 2]);
        }
        if (! $this->has('status')) {
            $this->merge(['status' => RecordStatus::Active->value]);
        }
    }
}
