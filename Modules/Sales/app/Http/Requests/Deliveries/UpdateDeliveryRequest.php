<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests\Deliveries;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Sales\Models\Delivery;

class UpdateDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Delivery::class);
    }

    public function rules(): array
    {
        return [
            'sales_order_id' => ['required', 'integer', 'exists:sales_orders,id'],
            'delivery_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:64'],
            'carrier' => ['nullable', 'string', 'max:128'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'tracking_number' => ['nullable', 'string', 'max:128'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sales_order_line_id' => ['required', 'integer', 'exists:sales_order_lines,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
