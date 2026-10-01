<?php

namespace App\Http\Requests\Designations;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('designation'));
    }

    public function rules(): array
    {
        $id = $this->route('designation')->id;

        return [
            'code' => [
                'sometimes', 'string', 'max:32',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('designations', 'code')->ignore($id),
            ],
            'name' => [
                'sometimes', 'string', 'max:128',
                Rule::unique('designations', 'name')->ignore($id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'level' => ['sometimes', 'integer', 'min:1', 'max:999'],
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
