<?php

declare(strict_types=1);

namespace App\Http\Requests\FinancialYears;

use App\Models\FinancialYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', FinancialYear::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:32', Rule::unique('financial_years', 'name')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'period_count' => ['nullable', 'integer', 'min:1', 'max:24'],
        ];
    }
}
