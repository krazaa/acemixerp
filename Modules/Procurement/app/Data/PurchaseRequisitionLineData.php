<?php

namespace Modules\Procurement\Data;

final readonly class PurchaseRequisitionLineData
{
    public function __construct(
        public int $itemId,
        public ?int $unitId,
        public string $quantity,
        public string $estimatedUnitPrice,
        public ?string $requiredDate = null,
        public ?string $specification = null,
        public int $position = 0,
        public ?int $brandId = null,
        public ?int $origindId = null,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row, int $position): self
    {
        return new self(
            itemId: (int) $row['item_id'],
            unitId: ! empty($row['unit_id']) ? (int) $row['unit_id'] : null,
            quantity: self::normalizeDecimal($row['quantity'] ?? 0),
            estimatedUnitPrice: self::normalizeDecimal($row['estimated_unit_price'] ?? 0),
            requiredDate: $row['required_date'] ?? null,
            specification: $row['specification'] ?? null,
            position: $position,
            brandId: filled($row['brand_id'] ?? null) ? (int) $row['brand_id'] : null,
            origindId: filled($row['origin_id'] ?? null) ? (int) $row['origin_id'] : null,
        );
    }

    public function lineTotal(): string
    {
        return bcmul($this->quantity, $this->estimatedUnitPrice, 4);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'item_id' => $this->itemId,
            'brand_id' => $this->brandId,
            'origin_id' => $this->origindId,
            'unit_id' => $this->unitId,
            'quantity' => $this->quantity,
            'estimated_unit_price' => $this->estimatedUnitPrice,
            'estimated_line_total' => $this->lineTotal(),
            'required_date' => $this->requiredDate,
            'specification' => $this->specification,
            'position' => $this->position,
        ];
    }

    private static function normalizeDecimal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.0000';
        }

        return number_format((float) $value, 4, '.', '');
    }
}
