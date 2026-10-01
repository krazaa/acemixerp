<?php

namespace Modules\Inventory\Http\Requests\Origins;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Inventory\Enums\OriginStatus;
use Modules\Inventory\Models\Origin;

class StoreOriginRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Origin::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128', Rule::unique('origins', 'name')],
            'code' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::enum(OriginStatus::class)],
        ];
    }
}
