<?php

namespace Modules\Sales\Data;

final readonly class DeliveryLineData
{
    public function __construct(
        public int $salesOrderLineId,
        public int $itemId,
        public ?int $unitId,
        public string $quantity,
        public string $unitPrice,
        public ?string $notes = null,
        public int $position = 0,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row, int $position): self
    {
        return new self(
            salesOrderLineId: (int) $row['sales_order_line_id'],
            itemId: (int) $row['item_id'],
            unitId: ! empty($row['unit_id']) ? (int) $row['unit_id'] : null,
            quantity: self::n($row['quantity'] ?? 0),
            unitPrice: self::n($row['unit_price'] ?? 0),
            notes: $row['notes'] ?? null,
            position: $position,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'sales_order_line_id' => $this->salesOrderLineId,
            'item_id' => $this->itemId,
            'unit_id' => $this->unitId,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'notes' => $this->notes,
            'position' => $this->position,
        ];
    }

    private static function n(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '0.0000';
        }

        return number_format((float) $v, 4, '.', '');
    }
}
