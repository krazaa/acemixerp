<?php

declare(strict_types=1);

namespace App\Http\Requests\CostCenters;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCostCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('cost_center'));
    }

    public function rules(): array
    {
        $id = $this->route('cost_center')->id;

        return [
            'code' => [
                'sometimes', 'string', 'max:32',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('cost_centers', 'code')->ignore($id),
            ],
            'name' => [
                'sometimes', 'string', 'max:128',
                Rule::unique('cost_centers', 'name')->ignore($id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'default_expense_account_id' => ['nullable', 'integer'],
            'status' => ['sometimes', Rule::enum(RecordStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }
}
