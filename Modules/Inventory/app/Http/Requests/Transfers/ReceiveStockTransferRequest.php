<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests\Transfers;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('receive', $this->route('transfer'));
    }

    public function rules(): array
    {
        return [
            'received' => ['required', 'array'],
            'received.*' => ['numeric', 'min:0'],
        ];
    }
}
