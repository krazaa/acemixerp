<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Contracts\LedgerService;
use App\Data\LedgerQuery;
use App\Enums\JournalEntryStatus;
use App\Models\JournalLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class GeneralLedgerService implements LedgerService
{
    public function lines(LedgerQuery $query): Collection
    {
        $q = $this->baseQuery($query)
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_lines.position');

        $rows = $q->get($this->selectColumns());

        // Compute running balance in PHP — avoids the mysql-only window function
        // and works identically on sqlite for tests.
        $running = '0.0000';

        return $rows->map(function ($row) use (&$running) {
            $running = bcadd($running, bcsub($row->debit, $row->credit, 4), 4);
            $row->running_balance = $running;

            return $row;
        });
    }

    public function balance(LedgerQuery $query): string
    {
        $row = $this->baseQuery($query)->first([
            DB::raw('COALESCE(SUM(journal_lines.debit), 0)  AS total_debit'),
            DB::raw('COALESCE(SUM(journal_lines.credit), 0) AS total_credit'),
        ]);

        return bcsub((string) $row->total_debit, (string) $row->total_credit, 4);
    }

    public function accountBalances(LedgerQuery $query): Collection
    {
        return $this->baseQuery($query)
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type', 'accounts.normal_balance')
            ->orderBy('accounts.code')
            ->get([
                'accounts.id as account_id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                'accounts.normal_balance',
                DB::raw('COALESCE(SUM(journal_lines.debit), 0)  AS debit_total'),
                DB::raw('COALESCE(SUM(journal_lines.credit), 0) AS credit_total'),
            ])
            ->map(function ($r) {
                // Balance in the account's normal direction:
                //  - debit-normal accounts: debit - credit
                //  - credit-normal accounts: credit - debit
                $isDebitNormal = in_array($r->normal_balance, ['debit'], true);
                $r->balance = $isDebitNormal
                    ? bcsub((string) $r->debit_total, (string) $r->credit_total, 4)
                    : bcsub((string) $r->credit_total, (string) $r->debit_total, 4);

                return $r;
            });
    }

    // ─── Internal ────────────────────────────────────────────────────

    private function baseQuery(LedgerQuery $query)
    {
        $q = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value);

        if ($query->accountId) {
            $q->where('journal_lines.account_id', $query->accountId);
        }
        if ($query->customerId) {
            $q->where('journal_lines.customer_id', $query->customerId);
        }
        if ($query->vendorId) {
            $q->where('journal_lines.vendor_id', $query->vendorId);
        }
        if ($query->costCenterId) {
            $q->where('journal_lines.cost_center_id', $query->costCenterId);
        }
        if ($query->departmentId) {
            $q->where('journal_lines.department_id', $query->departmentId);
        }

        if ($query->from) {
            $q->whereDate('journal_entries.entry_date', '>=', $query->from->format('Y-m-d'));
        }
        if ($query->to) {
            $q->whereDate('journal_entries.entry_date', '<=', $query->to->format('Y-m-d'));
        }

        if ($query->search) {
            $q->where(function ($q) use ($query) {
                $q->where('journal_entries.number', 'like', "%{$query->search}%")
                    ->orWhere('journal_entries.description', 'like', "%{$query->search}%")
                    ->orWhere('journal_entries.reference', 'like', "%{$query->search}%")
                    ->orWhere('journal_lines.memo', 'like', "%{$query->search}%");
            });
        }

        return $q;
    }

    private function selectColumns(): array
    {
        return [
            'journal_entries.id as journal_entry_id',
            'journal_entries.number',
            'journal_entries.entry_date',
            'journal_entries.description',
            'journal_entries.reference',
            'journal_lines.account_id',
            'journal_lines.debit',
            'journal_lines.credit',
            'journal_lines.memo as line_memo',
            DB::raw('(SELECT code FROM accounts WHERE accounts.id = journal_lines.account_id) AS account_code'),
            DB::raw('(SELECT name FROM accounts WHERE accounts.id = journal_lines.account_id) AS account_name'),
        ];
    }
}
