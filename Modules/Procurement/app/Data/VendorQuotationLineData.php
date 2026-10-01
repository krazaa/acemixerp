<?php

declare(strict_types=1);

namespace Modules\Procurement\Data;

final readonly class VendorQuotationLineData
{
    public function __construct(
        public int $rfqLineId,
        public int $itemId,
        public string $quantity,
        public string $unitPrice,
        public string $taxRate = '0.000000',
        public string $whtTaxRate = '0.000000',
        public ?int $leadTimeDays = null,
        public ?string $notes = null,
        public int $position = 0,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row, int $position): self
    {
        return new self(
            rfqLineId: (int) $row['rfq_line_id'],
            itemId: (int) $row['item_id'],
            quantity: self::normalizeDecimal($row['quantity'] ?? 0),
            unitPrice: self::normalizeDecimal($row['unit_price'] ?? 0),
            taxRate: self::normalizeDecimal($row['tax_rate'] ?? 0, 6),
            whtTaxRate: self::normalizeDecimal($row['wht_tax_rate'] ?? 0, 6),
            leadTimeDays: ! empty($row['lead_time_days']) ? (int) $row['lead_time_days'] : null,
            notes: $row['notes'] ?? null,
            position: $position,
        );
    }

    public function lineTotal(): string
    {
        return bcmul($this->quantity, $this->unitPrice, 4);
    }

    public function taxAmount(): string
    {
        $rate = bcdiv($this->taxRate, '100', 8);

        return bcmul($this->lineTotal(), $rate, 4);
    }

    public function whtTaxAmount(): string
    {
        $whtrate = bcdiv($this->whtTaxRate, '100', 8);

        return bcmul($this->lineTotal(), $whtrate, 4);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'rfq_line_id' => $this->rfqLineId,
            'item_id' => $this->itemId,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal(),
            'tax_rate' => $this->taxRate,
            'wht_tax_rate' => $this->whtTaxRate,
            'tax_amount' => $this->taxAmount(),
            'wht_tax_amount' => $this->whtTaxAmount(),
            'lead_time_days' => $this->leadTimeDays,
            'notes' => $this->notes,
            'position' => $this->position,
        ];
    }

    private static function normalizeDecimal(mixed $v, int $scale = 4): string
    {
        if ($v === null || $v === '') {
            return number_format(0, $scale, '.', '');
        }

        return number_format((float) $v, $scale, '.', '');
    }
}
