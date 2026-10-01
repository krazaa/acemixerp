<?php

namespace Modules\Procurement\Data;

final readonly class RfqLineData
{
    public function __construct(
        public int $itemId,
        public ?int $unitId,
        public string $quantity,
        public ?string $specification = null,
        public ?int $purchaseRequisitionLineId = null,
        public int $position = 0,
        public ?int $brandId = null,
        public ?int $originId = null,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row, int $position): self
    {
        return new self(
            itemId: (int) $row['item_id'],
            unitId: ! empty($row['unit_id']) ? (int) $row['unit_id'] : null,
            quantity: self::normalizeDecimal($row['quantity'] ?? 0),
            specification: $row['specification'] ?? null,
            purchaseRequisitionLineId: ! empty($row['purchase_requisition_line_id'])
                                            ? (int) $row['purchase_requisition_line_id'] : null,
            position: $position,
            brandId: filled($row['brand_id'] ?? null) ? (int) $row['brand_id'] : null,
            originId: filled($row['origin_id'] ?? null) ? (int) $row['origin_id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'item_id' => $this->itemId,
            'brand_id' => $this->brandId,
            'origin_id' => $this->originId,
            'unit_id' => $this->unitId,
            'quantity' => $this->quantity,
            'specification' => $this->specification,
            'purchase_requisition_line_id' => $this->purchaseRequisitionLineId,
            'position' => $this->position,
        ];
    }

    private static function normalizeDecimal(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '0.0000';
        }

        return number_format((float) $v, 4, '.', '');
    }
}
