<?php

declare(strict_types=1);

namespace Modules\Sales\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class QuotationData
{
    /** @param QuotationLineData[] $lines */
    public function __construct(
        public int $customerId,
        public \DateTimeInterface $quotationDate,
        public string $currencyCode,
        public array $lines,
        public ?\DateTimeInterface $validUntil = null,
        public ?int $salespersonId = null,
        public ?int $departmentId = null,
        public ?int $warehouseId = null,
        public ?int $paymentTermId = null,
        public ?string $reference = null,
        public ?string $notes = null,
        public ?string $terms = null,
        public string $exchangeRate = '1.00000000',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $raw = collect($request->input('lines', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['item_id']))
            ->values();

        $lines = $raw->map(fn ($row, $i) => QuotationLineData::fromArray($row, $i))->all();

        return new self(
            customerId: (int) $request->input('customer_id'),
            quotationDate: Carbon::parse($request->input('quotation_date')),
            currencyCode: strtoupper((string) ($request->input('currency_code') ?: 'PKR')),
            lines: $lines,
            validUntil: $request->date('valid_until'),
            salespersonId: $request->integer('salesperson_id') ?: null,
            departmentId: $request->integer('department_id') ?: null,
            warehouseId: $request->integer('warehouse_id') ?: null,
            paymentTermId: $request->integer('payment_term_id') ?: null,
            reference: $request->input('reference'),
            notes: $request->input('notes'),
            terms: $request->input('terms'),
        );
    }

    public function subtotal(): string
    {
        return array_reduce($this->lines,
            fn (string $c, QuotationLineData $l) => bcadd($c, $l->lineSubtotal(), 4), '0.0000');
    }

    public function discountTotal(): string
    {
        return array_reduce($this->lines,
            fn (string $c, QuotationLineData $l) => bcadd($c, $l->discountAmount(), 4), '0.0000');
    }

    public function taxTotal(): string
    {
        return array_reduce($this->lines,
            fn (string $c, QuotationLineData $l) => bcadd($c, $l->lineTax(), 4), '0.0000');
    }

    public function whtTaxTotal(): string
    {
        return array_reduce(
            $this->lines,
            fn (string $c, QuotationLineData $l) => bcadd($c, $l->lineWhtTax(), 4),
            '0.0000',
        );
    }

    public function total(): string
    {
        return bcsub(
            bcadd($this->subtotal(), $this->taxTotal(), 4),
            $this->whtTaxTotal(),
            4,
        );
    }
}
