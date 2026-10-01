<?php

declare(strict_types=1);

namespace Modules\Procurement\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class SupplierInvoiceData
{
    /** @param SupplierInvoiceLineData[] $lines */
    public function __construct(
        public int $vendorId,
        public ?int $purchaseOrderId,
        public string $vendorInvoiceNumber,
        public \DateTimeInterface $invoiceDate,
        public \DateTimeInterface $dueDate,
        public string $currencyCode,
        public array $lines,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $raw = collect($request->input('lines', []))
            ->filter(fn ($row) => is_array($row) && (! empty($row['purchase_order_line_id']) || ! empty($row['expense_account_id'])))
            ->values();

        $lines = $raw->map(fn ($row, $i) => SupplierInvoiceLineData::fromArray($row, $i))->all();

        return new self(
            vendorId: (int) $request->input('vendor_id'),
            purchaseOrderId: filled($request->input('purchase_order_id')) ? (int) $request->input('purchase_order_id') : null,
            vendorInvoiceNumber: (string) $request->input('vendor_invoice_number'),
            invoiceDate: Carbon::parse($request->input('invoice_date')),
            dueDate: Carbon::parse($request->input('due_date')),
            currencyCode: strtoupper((string) ($request->input('currency_code') ?: 'USD')),
            lines: $lines,
            notes: $request->input('notes'),
        );
    }

    public function subtotal(): string
    {
        return array_reduce($this->lines,
            fn (string $c, SupplierInvoiceLineData $l) => bcadd($c, $l->lineSubtotal(), 4), '0.0000');
    }

    public function taxTotal(): string
    {
        return array_reduce($this->lines,
            fn (string $c, SupplierInvoiceLineData $l) => bcadd($c, $l->lineTax(), 4), '0.0000');
    }

    public function whtTaxTotal(): string
    {
        return array_reduce($this->lines,
            fn (string $c, SupplierInvoiceLineData $l) => bcadd($c, $l->lineWhtTax(), 4), '0.0000');
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
