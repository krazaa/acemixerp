<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests\Counts;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Inventory\Models\StockCount;

class StoreStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StockCount::class);
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'count_date' => ['required', 'date'],
            'scope' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
