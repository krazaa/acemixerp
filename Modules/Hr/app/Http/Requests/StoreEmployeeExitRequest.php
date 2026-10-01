<?php

namespace Modules\Hr\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeExitRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return ['employee_id' => ['required', 'exists:employees,id'], 'exit_date' => ['required', 'date'], 'reason' => ['required', 'string', 'max:500'], 'notes' => ['nullable', 'string', 'max:2000']];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('hr.manage') ?? false;
    }
}
