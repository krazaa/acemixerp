<?php

declare(strict_types=1);

namespace Modules\Sales\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class CustomerReceiptData
{
    /** @param ReceiptAllocationData[] $allocations */
    public function __construct(
        public int $customerId,
        public \DateTimeInterface $receiptDate,
        public string $currencyCode,
        public string $amount,
        public string $paymentMethod,
        public array $allocations = [],
        public ?int $bankAccountId = null,
        public ?string $reference = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $rows = collect($request->input('allocations', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['sales_invoice_id']) && (float) ($row['amount'] ?? 0) > 0)
            ->values();

        $allocations = $rows->map(fn ($row) => ReceiptAllocationData::fromArray($row))->all();

        $explicitAmount = (string) ($request->input('amount') ?: '0');
        $allocationSum = array_reduce(
            $allocations,
            fn (string $c, ReceiptAllocationData $a) => bcadd($c, $a->amount, 4),
            '0.0000',
        );

        $amount = bccomp($explicitAmount, '0', 4) > 0 ? $explicitAmount : $allocationSum;

        return new self(
            customerId: (int) $request->input('customer_id'),
            receiptDate: Carbon::parse($request->input('receipt_date', now())),
            currencyCode: strtoupper((string) ($request->input('currency_code') ?: 'USD')),
            amount: number_format((float) $amount, 4, '.', ''),
            paymentMethod: (string) ($request->input('payment_method') ?: 'bank_transfer'),
            allocations: $allocations,
            bankAccountId: $request->integer('bank_account_id') ?: null,
            reference: $request->input('reference'),
            notes: $request->input('notes'),
        );
    }

    public function allocationTotal(): string
    {
        return array_reduce($this->allocations,
            fn (string $c, ReceiptAllocationData $a) => bcadd($c, $a->amount, 4), '0.0000');
    }
}
