<?php

// app/Contracts/AccountingEngine.php

namespace Modules\Hr\Contracts;

interface AccountingEngine
{
    /**
     * Post a balanced journal from a source document.
     *
     * @param  string  $sourceType  e.g. 'payroll', 'loan_disbursement'
     * @param  array  $lines  [['account_code' => '...', 'debit' => 0, 'credit' => 0, 'memo' => '...'], ...]
     * @return int journal_id
     */
    public function post(string $sourceType, int $sourceId, array $lines, array $meta = []): int;

    /**
     * Resolve a configured system account code by key.
     * e.g. 'salary_expense', 'payroll_payable', 'loan_receivable', 'tax_payable'
     */
    public function systemAccount(string $key): string;
}
