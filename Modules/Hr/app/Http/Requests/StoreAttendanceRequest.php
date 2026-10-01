<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return ['employee_id' => ['required', 'exists:employees,id'], 'attendance_date' => ['required', 'date'], 'checked_in_at' => ['nullable', 'date'], 'checked_out_at' => ['nullable', 'date', 'after:checked_in_at'], 'status' => ['required', 'in:present,absent,late,leave'], 'notes' => ['nullable', 'string', 'max:2000']];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('attendance.manage') ?? false;
    }
}
