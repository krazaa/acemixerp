<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hr.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($this->route('employee'))],
            'personal_email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'personal_email')->ignore($this->route('employee'))],
            'phone' => ['nullable', 'string', 'max:32'],
            'personal_phone' => ['nullable', 'string', 'max:32'],
            'department_id' => ['required', 'exists:departments,id'],
            'designation_id' => ['required', 'exists:designations,id'],
            'salary_structure_id' => ['nullable', 'exists:salary_structures,id'],
            'leave_policy_id' => ['nullable', 'exists:leave_policies,id'],
            'joining_date' => ['required', 'date'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'hire_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:32'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:150'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
            'attendance_enabled' => ['nullable', 'boolean'],
            'contract_type' => ['required', 'in:probation,permanent,fixed_term'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:joining_date'],
            'monthly_salary' => ['required', 'numeric', 'min:0'],
        ];
    }
}
