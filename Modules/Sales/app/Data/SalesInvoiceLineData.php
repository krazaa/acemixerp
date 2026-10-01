<?php

declare(strict_types=1);

namespace Modules\Sales\Data;

final readonly class SalesInvoiceLineData
{
    public function __construct(
        public int $itemId,
        public ?int $unitId,
        public string $quantity,
        public string $unitPrice,
        public string $discountPercent = '0.000000',
        public string $taxRate = '0.000000',
        public string $whtTaxRate = '0.000000',
        public ?string $description = null,
        public ?int $salesOrderLineId = null,
        public ?int $deliveryLineId = null,
        public int $position = 0,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row, int $position): self
    {
        return new self(
            itemId: (int) $row['item_id'],
            unitId: ! empty($row['unit_id']) ? (int) $row['unit_id'] : null,
            quantity: self::n($row['quantity'] ?? 0),
            unitPrice: self::n($row['unit_price'] ?? 0),
            discountPercent: self::n($row['discount_percent'] ?? 0, 6),
            taxRate: self::n($row['tax_rate'] ?? 0, 6),
            whtTaxRate: self::n($row['wht_tax_rate'] ?? 0, 6),
            description: $row['description'] ?? null,
            salesOrderLineId: ! empty($row['sales_order_line_id']) ? (int) $row['sales_order_line_id'] : null,
            deliveryLineId: ! empty($row['delivery_line_id']) ? (int) $row['delivery_line_id'] : null,
            position: $position,
        );
    }

    public function gross(): string
    {
        return bcmul($this->quantity, $this->unitPrice, 4);
    }

    public function discountAmount(): string
    {
        return bcmul($this->gross(), bcdiv($this->discountPercent, '100', 8), 4);
    }

    public function lineSubtotal(): string
    {
        return bcsub($this->gross(), $this->discountAmount(), 4);
    }

    public function lineTax(): string
    {
        return bcmul($this->lineSubtotal(), bcdiv($this->taxRate, '100', 8), 4);
    }

    public function lineWhtTax(): string
    {
        return bcmul($this->lineSubtotal(), bcdiv($this->whtTaxRate, '100', 8), 4);
    }

    public function lineTotal(): string
    {
        return bcsub(
            bcadd($this->lineSubtotal(), $this->lineTax(), 4),
            $this->lineWhtTax(),
            4,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'sales_order_line_id' => $this->salesOrderLineId,
            'delivery_line_id' => $this->deliveryLineId,
            'item_id' => $this->itemId,
            'unit_id' => $this->unitId,
            'position' => $this->position,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'discount_percent' => $this->discountPercent,
            'discount_amount' => $this->discountAmount(),
            'tax_rate' => $this->taxRate,
            'wht_tax_rate' => $this->whtTaxRate,
            'line_subtotal' => $this->lineSubtotal(),
            'line_tax' => $this->lineTax(),
            'line_wht_tax' => $this->lineWhtTax(),
            'line_total' => $this->lineTotal(),
            'description' => $this->description,
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
