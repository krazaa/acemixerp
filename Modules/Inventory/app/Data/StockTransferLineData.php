<?php

declare(strict_types=1);

namespace Modules\Inventory\Data;

final readonly class StockTransferLineData
{
    public function __construct(
        public int $itemId,
        public ?int $unitId,
        public ?int $batchId,
        public string $quantity,
        public string $unitCost = '0.0000',
        public ?string $notes = null,
        public int $position = 0,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row, int $position): self
    {
        return new self(
            itemId: (int) $row['item_id'],
            unitId: ! empty($row['unit_id']) ? (int) $row['unit_id'] : null,
            batchId: ! empty($row['batch_id']) ? (int) $row['batch_id'] : null,
            quantity: self::n($row['quantity'] ?? 0),
            unitCost: self::n($row['unit_cost'] ?? 0),
            notes: $row['notes'] ?? null,
            position: $position,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'item_id' => $this->itemId,
            'unit_id' => $this->unitId,
            'batch_id' => $this->batchId,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unitCost,
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
