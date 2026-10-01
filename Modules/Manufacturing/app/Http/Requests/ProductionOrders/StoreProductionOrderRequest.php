<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Http\Requests\ProductionOrders;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Manufacturing\Models\ProductionOrder;

class StoreProductionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ProductionOrder::class);
    }

    public function rules(): array
    {
        return [
            'bill_of_materials_id' => ['required', 'integer', 'exists:bill_of_materials,id'],
            'product_id' => ['required', 'integer', 'exists:items,id'],
            'planned_quantity' => ['required', 'numeric', 'gt:0'],
            'source_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'scheduled_start_date' => ['nullable', 'date'],
            'scheduled_end_date' => ['nullable', 'date', 'after_or_equal:scheduled_start_date'],
            'sales_order_id' => ['nullable', 'integer', 'exists:sales_orders,id'],
            'type' => ['required', 'in:standard,make_to_order,assembly,repack'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
