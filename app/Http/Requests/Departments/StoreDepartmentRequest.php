<?php

namespace App\Http\Requests\Departments;

use App\Enums\RecordStatus;
use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Department::class);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:32',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('departments', 'code'),
            ],
            'name' => [
                'required', 'string', 'max:128',
                Rule::unique('departments', 'name'),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
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
