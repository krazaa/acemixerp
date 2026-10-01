<?php

declare(strict_types=1);

namespace Modules\Sales\Data;

final readonly class ReceiptAllocationData
{
    public function __construct(
        public int $salesInvoiceId,
        public string $amount,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            salesInvoiceId: (int) $row['sales_invoice_id'],
            amount: number_format((float) ($row['amount'] ?? 0), 4, '.', ''),
        );
    }
}
