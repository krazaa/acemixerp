<?php

declare(strict_types=1);

namespace Modules\Procurement\Data;

final readonly class SupplierInvoiceLineData
{
    public function __construct(
        public ?int $purchaseOrderLineId,
        public ?int $itemId,
        public ?int $expenseAccountId,
        public string $quantity,
        public string $unitPrice,
        public string $taxRate = '0.000000',
        public string $whtRate = '0.000000',
        public ?string $description = null,
        public int $position = 0,
        public ?int $brandId = null,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row, int $position): self
    {
        return new self(
            purchaseOrderLineId: filled($row['purchase_order_line_id'] ?? null) ? (int) $row['purchase_order_line_id'] : null,
            itemId: filled($row['item_id'] ?? null) ? (int) $row['item_id'] : null,
            expenseAccountId: filled($row['expense_account_id'] ?? null) ? (int) $row['expense_account_id'] : null,
            quantity: self::n($row['quantity'] ?? 0),
            unitPrice: self::n($row['unit_price'] ?? 0),
            taxRate: self::n($row['tax_rate'] ?? 0, 6),
            whtRate: self::n($row['wht_rate'] ?? 0, 6),
            description: $row['description'] ?? null,
            position: $position,
            brandId: filled($row['brand_id'] ?? null) ? (int) $row['brand_id'] : null,
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

    public function lineTotal(): string
    {
        return bcadd($this->lineSubtotal(), $this->lineTax(), 4);
    }

    public function lineWhtTax(): string
    {
        $rate = bcdiv($this->whtRate, '100', 8);

        return bcmul($this->lineTotal(), $rate, 4);
    }

    public function lineTotalAfterWhtTax(): string
    {
        return bcsub($this->lineTotal(), $this->lineWhtTax(), 4);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'purchase_order_line_id' => $this->purchaseOrderLineId,
            'item_id' => $this->itemId,
            'brand_id' => $this->brandId,
            'expense_account_id' => $this->expenseAccountId,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'tax_rate' => $this->taxRate,
            'wht_rate' => $this->whtRate,
            'line_subtotal' => $this->lineSubtotal(),
            'line_tax' => $this->lineTax(),
            'line_total' => $this->lineTotal(),
            'line_wht_tax' => $this->lineWhtTax(),
            'line_total_wht_tax' => $this->lineTotalAfterWhtTax(),
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
