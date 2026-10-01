<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use Illuminate\Support\Collection;

interface BankReconciliationManager
{
    public function openFor(BankAccount $account, \DateTimeInterface $statementDate, array $opening, array $closing): BankReconciliation;

    public function importStatementLines(BankReconciliation $reconciliation, array $rows): int;

    public function match(BankStatementLine $line, int $journalEntryId, int $userId): BankStatementLine;

    public function unmatch(BankStatementLine $line): BankStatementLine;

    public function complete(BankReconciliation $reconciliation, int $userId): BankReconciliation;

    public function unmatchedStatementLines(BankReconciliation $reconciliation): Collection;

    public function unmatchedLedgerEntries(BankReconciliation $reconciliation): Collection;
}
