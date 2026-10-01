<?php

namespace Modules\Procurement\Services;

use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\Origin;
use Modules\Procurement\Models\PurchaseOrderLine;

final class LineOriginService
{
    public function resolve(?int $originId, int $position): ?int
    {
        if ($originId === null) {
            return null;
        }
        if (! Origin::query()->whereKey($originId)->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(["lines.{$position}.origin_id" => 'The selected origin is no longer available.']);
        }

        return $originId;
    }

    public function forOrderLine(?int $originId, ?int $orderLineId, ?int $orderId, ?int $itemId, int $position): ?int
    {
        if ($orderLineId === null) {
            return $this->resolve($originId, $position);
        }
        $line = PurchaseOrderLine::query()->whereKey($orderLineId)->where('purchase_order_id', $orderId)->lockForUpdate()->first();
        if (! $line || (int) $line->item_id !== $itemId) {
            throw ValidationException::withMessages(["lines.{$position}.purchase_order_line_id" => 'The selected line must belong to this purchase order and item.']);
        }
        if ($line->origin_id !== null && $originId !== null && (int) $line->origin_id !== $originId) {
            throw ValidationException::withMessages(["lines.{$position}.origin_id" => 'The origin must match the purchase order line.']);
        }

        return $this->resolve($line->origin_id ?? $originId, $position);
    }
}
