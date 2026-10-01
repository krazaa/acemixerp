<?php

namespace App\Http\Requests\Departments;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('department'));
    }

    public function rules(): array
    {
        $id = $this->route('department')->id;

        return [
            'code' => [
                'sometimes', 'string', 'max:32',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('departments', 'code')->ignore($id),
            ],
            'name' => [
                'sometimes', 'string', 'max:128',
                Rule::unique('departments', 'name')->ignore($id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', Rule::enum(RecordStatus::class)],
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
    }
}
