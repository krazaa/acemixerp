<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransitionSalesReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->route('step'), ['approve', 'reject', 'receive', 'inspect', 'accept', 'credit', 'post'], true)
            && $this->user()->can($this->route('step'), $this->route('salesReturn'));
    }

    public function rules(): array
    {
        return match ($this->route('step')) {
            'reject' => ['rejection_reason' => ['required', 'string', 'min:3', 'max:2000']],
            'receive','inspect' => [
                'lines' => ['required', 'array', 'min:1', 'max:100'],
                'lines.*.id' => ['required', 'integer', 'distinct'],
                'lines.*.quantity' => ['required', 'numeric', 'decimal:0,4', 'min:0', 'max:999999999'],
                'lines.*.notes' => ['nullable', 'string', 'max:2000'],
            ],
            default => [],
        };
    }
}
