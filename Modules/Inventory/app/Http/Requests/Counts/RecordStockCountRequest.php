<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests\Counts;

use Illuminate\Foundation\Http\FormRequest;

class RecordStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('count'));
    }

    public function rules(): array
    {
        return [
            'counted' => ['required', 'array'],
            'counted.*' => ['numeric', 'min:0'],
        ];
    }
}
