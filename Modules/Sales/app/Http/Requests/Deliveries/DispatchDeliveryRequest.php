<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests\Deliveries;

use Illuminate\Foundation\Http\FormRequest;

class DispatchDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dispatch', $this->route('delivery')) ?? false;
    }

    public function rules(): array
    {
        return [
            'vehicle_number' => ['required', 'string', 'max:64'],
            'driver_name' => ['required', 'string', 'max:128'],
            'driver_contact' => ['required', 'string', 'regex:/^[0-9+()\\-\\s]{7,32}$/'],
            'driver_cnic' => ['required', 'digits:13'],
            'bilty_number' => ['required', 'string', 'max:64'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('driver_cnic')) {
            $this->merge([
                'driver_cnic' => preg_replace('/\\D/', '', (string) $this->input('driver_cnic')),
            ]);
        }
    }
}
