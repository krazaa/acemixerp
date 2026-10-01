<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests\Adjustments;

class UpdateStockAdjustmentRequest extends StoreStockAdjustmentRequest
{
    public function authorize(): bool
    {
        $adjustment = $this->route('adjustment');

        return $adjustment !== null && ($this->user()?->can('update', $adjustment) ?? false);
    }
}
