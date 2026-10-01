<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRunRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return ['period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start']];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('payroll.approve') ?? false;
    }
}
