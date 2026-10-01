<?php

namespace Modules\Procurement\Data;

final readonly class PurchaseOrderLineData
{
    public function __construct(
        public int $itemId,
        public ?int $unitId,
        public string $quantity,
        public string $unitPrice,
        public string $taxRate = '0.000000',
        public ?string $requiredDate = null,
        public ?string $specification = null,
        public ?int $rfqLineId = null,
        public int $position = 0,
        public ?int $brandId = null,
        public ?int $originId = null,
        public string $whtTaxRate = '0.000000',
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row, int $position): self
    {
        return new self(
            itemId: (int) $row['item_id'],
            unitId: ! empty($row['unit_id']) ? (int) $row['unit_id'] : null,
            quantity: self::n($row['quantity'] ?? 0),
            unitPrice: self::n($row['unit_price'] ?? 0),
            taxRate: self::n($row['tax_rate'] ?? 0, 6),
            whtTaxRate: self::n($row['wht_tax_rate'] ?? 0, 6),
            requiredDate: $row['required_date'] ?? null,
            specification: $row['specification'] ?? null,
            rfqLineId: ! empty($row['rfq_line_id']) ? (int) $row['rfq_line_id'] : null,
            position: $position,
            brandId: filled($row['brand_id'] ?? null) ? (int) $row['brand_id'] : null,
            originId: filled($row['origin_id'] ?? null) ? (int) $row['origin_id'] : null,
        );
    }

    public function lineSubtotal(): string
    {
        return bcmul($this->quantity, $this->unitPrice, 4);
    }

    public function lineTax(): string
    {
        $rate = bcdiv($this->taxRate, '100', 8);

        return bcmul($this->lineSubtotal(), $rate, 4);
    }

    public function whtLineTax(): string
    {
        return bcmul($this->lineSubtotal(), bcdiv($this->whtTaxRate, '100', 8), 4);
    }

    public function lineTotal(): string
    {
        return bcadd($this->lineSubtotal(), $this->lineTax(), 4);
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
            'unit_price' => $this->unitPrice,
            'tax_rate' => $this->taxRate,
            'wht_tax_rate' => $this->whtTaxRate,
            'wht_line_tax' => $this->whtLineTax(),
            'line_subtotal' => $this->lineSubtotal(),
            'line_tax' => $this->lineTax(),
            'line_total' => $this->lineTotal(),
            'required_date' => $this->requiredDate,
            'specification' => $this->specification,
            'rfq_line_id' => $this->rfqLineId,
            'position' => $this->position,
        ];
    }

    private static function n(mixed $v, int $scale = 4): string
    {
        if ($v === null || $v === '') {
            return number_format(0, $scale, '.', '');
        }

        return number_format((float) $v, $scale, '.', '');
    }
}
