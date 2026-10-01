<?php

namespace Modules\Inventory\Http\Requests\Origins;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Inventory\Enums\OriginStatus;

class UpdateOriginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('origins'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128', Rule::unique('origins', 'name')->ignore($this->route('origin'))],
            'code' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::enum(OriginStatus::class)],
        ];
    }
}
