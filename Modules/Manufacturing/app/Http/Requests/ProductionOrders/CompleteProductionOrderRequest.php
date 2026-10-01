<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Http\Requests\ProductionOrders;

use Illuminate\Foundation\Http\FormRequest;

class CompleteProductionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('complete', $this->route('productionOrder'));
    }

    public function rules(): array
    {
        return [
            'produced_quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'produced_quantity.required' => 'Enter the actual quantity produced.',
            'produced_quantity.gt' => 'Produced quantity must be greater than zero.',
        ];
    }
}
