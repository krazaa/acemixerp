<?php

declare(strict_types=1);

namespace App\Http\Requests\Designations;

use App\Enums\RecordStatus;
use App\Models\Designation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Designation::class);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:32',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('designations', 'code'),
            ],
            'name' => [
                'required', 'string', 'max:128',
                Rule::unique('designations', 'name'),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'level' => ['required', 'integer', 'min:1', 'max:999'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Code may only contain uppercase letters, digits, underscores, and dashes.',
            'level.min' => 'Level must be a positive integer (1 = most senior).',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
        if (! $this->has('level')) {
            $this->merge(['level' => 100]);
        }
        if (! $this->has('status')) {
            $this->merge(['status' => RecordStatus::Active->value]);
        }
    }
}
