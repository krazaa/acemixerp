<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Requests\Rfqs;

use Illuminate\Foundation\Http\FormRequest;

class AwardRfqLinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('award', $this->route('rfq')) ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'selections' => ['required', 'array', 'min:1'],
            'selections.*' => ['required', 'integer', 'distinct', 'min:1'],
        ];
    }
}
