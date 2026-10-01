<?php

namespace Modules\Procurement\Data;

final readonly class PaymentAllocationData
{
    public function __construct(
        public int $supplierInvoiceId,
        public string $amount,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            supplierInvoiceId: (int) $row['supplier_invoice_id'],
            amount: number_format((float) ($row['amount'] ?? 0), 4, '.', ''),
        );
    }
}
