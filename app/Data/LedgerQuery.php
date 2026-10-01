<?php

namespace App\Data;

final readonly class LedgerQuery
{
    public function __construct(
        public ?int $accountId = null,
        public ?int $customerId = null,
        public ?int $vendorId = null,
        public ?int $costCenterId = null,
        public ?int $departmentId = null,
        public ?\DateTimeInterface $from = null,
        public ?\DateTimeInterface $to = null,
        public ?string $search = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'account_id' => $this->accountId,
            'customer_id' => $this->customerId,
            'vendor_id' => $this->vendorId,
            'cost_center_id' => $this->costCenterId,
            'department_id' => $this->departmentId,
            'from' => $this->from?->format('Y-m-d'),
            'to' => $this->to?->format('Y-m-d'),
            'search' => $this->search,
        ], fn ($v) => $v !== null);
    }
}
