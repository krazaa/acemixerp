<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountingPeriods;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePeriodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('year.create');
    }

    public function rules(): array
    {
        return [
            'months' => ['required', 'integer', 'min:1', 'max:24'],
        ];
    }
}
