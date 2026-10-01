<?php

declare(strict_types=1);

namespace Modules\Reports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinancialReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.financial') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['as_of' => $this->input('as_of') ?: now()->toDateString()]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $rules = [
            'as_of' => ['required', 'date_format:Y-m-d'],
            'currency' => ['nullable', 'string', 'regex:/^[A-Z]{3}$/'],
        ];

        if ($this->routeIs('reports.accounts-payable', 'reports.accounts-receivable')) {
            $table = $this->routeIs('reports.accounts-payable') ? 'vendors' : 'customers';
            $rules['party_id'] = ['nullable', 'integer', 'exists:'.$table.',id'];
        }

        return $rules;
    }
}
