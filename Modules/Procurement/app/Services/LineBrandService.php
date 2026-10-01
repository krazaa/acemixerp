<?php

namespace Modules\Procurement\Services;

use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\Brand;
use Modules\Procurement\Models\PurchaseOrderLine;

final class LineBrandService
{
    public function resolve(?int $brandId, int $position): ?int
    {
        if ($brandId === null) {
            return null;
        }
        if (! Brand::query()->whereKey($brandId)->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(["lines.{$position}.brand_id" => 'The selected brand is no longer available.']);
        }

        return $brandId;
    }

    public function forOrderLine(?int $brandId, ?int $orderLineId, ?int $orderId, ?int $itemId, int $position): ?int
    {
        if ($orderLineId === null) {
            return $this->resolve($brandId, $position);
        }
        $line = PurchaseOrderLine::query()->whereKey($orderLineId)->where('purchase_order_id', $orderId)->lockForUpdate()->first();
        if (! $line || (int) $line->item_id !== $itemId) {
            throw ValidationException::withMessages(["lines.{$position}.purchase_order_line_id" => 'The selected line must belong to this purchase order and item.']);
        }
        if ($line->brand_id !== null && $brandId !== null && (int) $line->brand_id !== $brandId) {
            throw ValidationException::withMessages(["lines.{$position}.brand_id" => 'The brand must match the purchase order line.']);
        }

        return $this->resolve($line->brand_id ?? $brandId, $position);
    }
}
