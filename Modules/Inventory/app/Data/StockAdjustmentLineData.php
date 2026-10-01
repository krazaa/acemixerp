<?php

declare(strict_types=1);

namespace Modules\Inventory\Data;

final readonly class StockAdjustmentLineData
{
    public function __construct(
        public int $itemId,
        public string $quantity,        // signed: positive = add, negative = remove
        public string $unitCost = '0.0000',
        public ?int $batchId = null,
        public ?string $notes = null,
        public int $position = 0,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row, int $position): self
    {
        return new self(
            itemId: (int) $row['item_id'],
            quantity: self::n($row['quantity'] ?? 0),
            unitCost: self::n($row['unit_cost'] ?? 0),
            batchId: ! empty($row['batch_id']) ? (int) $row['batch_id'] : null,
            notes: $row['notes'] ?? null,
            position: $position,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'item_id' => $this->itemId,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unitCost,
            'batch_id' => $this->batchId,
            'notes' => $this->notes,
            'position' => $this->position,
        ];
    }

    private static function n(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '0.0000';
        }

        // Preserve the sign for adjustments.
        return number_format((float) $v, 4, '.', '');
    }
}
