<?php

declare(strict_types=1);

namespace App\Http\Requests\CostCenters;

use App\Enums\RecordStatus;
use App\Models\CostCenter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCostCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CostCenter::class);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:32',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('cost_centers', 'code'),
            ],
            'name' => [
                'required', 'string', 'max:128',
                Rule::unique('cost_centers', 'name'),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],

            // Phase 3 will add: 'exists:accounts,id'
            'default_expense_account_id' => ['nullable', 'integer'],

            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Code may only contain uppercase letters, digits, underscores, and dashes.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
        if (! $this->has('status')) {
            $this->merge(['status' => RecordStatus::Active->value]);
        }
    }
}
