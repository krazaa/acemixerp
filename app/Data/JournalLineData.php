<?php

namespace App\Data;

final readonly class JournalLineData
{
    public function __construct(
        public int $accountId,
        public string $debit,
        public string $credit,
        public ?string $memo = null,
        public ?int $costCenterId = null,
        public ?int $departmentId = null,
        public ?int $customerId = null,
        public ?int $vendorId = null,
        public int $position = 0,
        public ?int $employeeId = null,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row, int $position): self
    {
        return new self(
            accountId: (int) $row['account_id'],
            debit: self::normalizeAmount($row['debit'] ?? 0),
            credit: self::normalizeAmount($row['credit'] ?? 0),
            memo: $row['memo'] ?? null,
            costCenterId: ! empty($row['cost_center_id']) ? (int) $row['cost_center_id'] : null,
            departmentId: ! empty($row['department_id']) ? (int) $row['department_id'] : null,
            customerId: ! empty($row['customer_id']) ? (int) $row['customer_id'] : null,
            vendorId: ! empty($row['vendor_id']) ? (int) $row['vendor_id'] : null,
            position: $position,
            employeeId: ! empty($row['employee_id']) ? (int) $row['employee_id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'memo' => $this->memo,
            'cost_center_id' => $this->costCenterId,
            'department_id' => $this->departmentId,
            'customer_id' => $this->customerId,
            'vendor_id' => $this->vendorId,
            'employee_id' => $this->employeeId,
            'position' => $this->position,
        ];
    }

    private static function normalizeAmount(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return number_format((float) $value, 4, '.', '');
    }
}
