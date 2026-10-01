<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\LedgerQuery;
use Illuminate\Support\Collection;

interface LedgerService
{
    /**
     * @return Collection<int, object> lines with columns:
     *                                 journal_entry_id, number, entry_date, description, reference,
     *                                 account_id, account_code, account_name, debit, credit, running_balance
     */
    public function lines(LedgerQuery $query): Collection;

    /** Aggregate signed balance for the given query (debit - credit). */
    public function balance(LedgerQuery $query): string;

    /**
     * Account balances across a period. Returns rows:
     *   account_id, code, name, type, debit_total, credit_total, balance
     *
     * @return Collection<int, object>
     */
    public function accountBalances(LedgerQuery $query): Collection;
}
