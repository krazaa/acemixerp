<?php

namespace Modules\Sales\Data;

final readonly class QuotationLineData
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
            position: $position,
        );
    }

    /**
     * Gross amount before discount: quantity × unit price.
     */
    public function grossAmount(): string
    {
        return bcmul($this->quantity, $this->unitPrice, 4);
    }

    /**
     * Discount amount: gross × discount percent.
     */
    public function discountAmount(): string
    {
        return bcmul(
            $this->grossAmount(),
            bcdiv($this->discountPercent, '100', 8),
            4,
        );
    }

    /**
     * Taxable base: gross − discount.
     * This is what tax, WHT, and everything downstream is computed from.
     */
    public function lineSubtotal(): string
    {
        return bcsub($this->grossAmount(), $this->discountAmount(), 4);
    }

    /**
     * Sales tax (GST/VAT) on the subtotal.
     */
    public function lineTax(): string
    {
        return bcmul($this->lineSubtotal(), bcdiv($this->taxRate, '100', 8), 4);
    }

    /**
     * Withholding tax on this line.
     *
     * Base = subtotal plus sales tax, matching the quotation edit form
     * and the persisted line recalculation.
     */
    public function lineWhtTax(): string
    {
        return bcmul(
            bcadd($this->lineSubtotal(), $this->lineTax(), 4),
            bcdiv($this->whtTaxRate, '100', 8),
            4,
        );
    }

    /**
     * Line total: subtotal + tax − WHT.
     * WHT is a deduction — the customer withholds it from the payment.
     */
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
            'item_id' => $this->itemId,
            'unit_id' => $this->unitId,
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
