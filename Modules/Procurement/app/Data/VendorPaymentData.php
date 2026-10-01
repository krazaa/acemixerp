<?php

namespace Modules\Procurement\Data;

use Carbon\Carbon;
use Illuminate\Http\Request;

final readonly class VendorPaymentData
{
    /** @param PaymentAllocationData[] $allocations */
    public function __construct(
        public int $vendorId,
        public \DateTimeInterface $paymentDate,
        public string $billingMonth,
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
            ->filter(fn ($row) => is_array($row) && ! empty($row['supplier_invoice_id']) && (float) ($row['amount'] ?? 0) > 0)
            ->values();

        $allocations = $rows->map(fn ($row) => PaymentAllocationData::fromArray($row))->all();

        $explicitAmount = (string) ($request->input('amount') ?: '0');
        $allocationSum = array_reduce(
            $allocations,
            fn (string $c, PaymentAllocationData $a) => bcadd($c, $a->amount, 4),
            '0.0000',
        );

        // If the user left the payment amount blank, derive it from the allocations.
        $amount = bccomp($explicitAmount, '0', 4) > 0 ? $explicitAmount : $allocationSum;

        $paymentDate = Carbon::parse($request->input('payment_date', now()));

        return new self(
            vendorId: (int) $request->input('vendor_id'),
            paymentDate: $paymentDate,
            billingMonth: (string) ($request->input('billing_month') ?: $paymentDate->format('Y-m')),
            currencyCode: strtoupper((string) ($request->input('currency_code') ?: 'PKR')),
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
        return array_reduce(
            $this->allocations,
            fn (string $c, PaymentAllocationData $a) => bcadd($c, $a->amount, 4),
            '0.0000',
        );
    }
}
