<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Contracts\BankReconciliationManager;
use App\Enums\ReconciliationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class BankReconciliationService implements BankReconciliationManager
{
    public function openFor(BankAccount $account, \DateTimeInterface $statementDate, array $opening, array $closing): BankReconciliation
    {
        return BankReconciliation::query()->firstOrCreate(
            [
                'bank_account_id' => $account->id,
                'statement_date' => $statementDate->format('Y-m-d'),
            ],
            [
                'statement_opening_balance' => $opening['amount'] ?? 0,
                'statement_closing_balance' => $closing['amount'] ?? 0,
                'status' => ReconciliationStatus::Open,
                'created_by' => Auth::id(),
            ],
        );
    }

    public function importStatementLines(BankReconciliation $reconciliation, array $rows): int
    {
        if ($reconciliation->status === ReconciliationStatus::Reconciled) {
            throw BusinessRuleException::make('Cannot import lines into a reconciled statement.');
        }

        return DB::transaction(function () use ($reconciliation, $rows) {
            $count = 0;
            foreach ($rows as $row) {
                BankStatementLine::query()->create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'transaction_date' => $row['transaction_date'],
                    'reference' => $row['reference'] ?? null,
                    'description' => $row['description'],
                    'debit' => $row['debit'] ?? 0,
                    'credit' => $row['credit'] ?? 0,
                ]);
                $count++;
            }

            return $count;
        });
    }

    public function match(BankStatementLine $line, int $journalEntryId, int $userId): BankStatementLine
    {
        if ($line->isMatched()) {
            throw BusinessRuleException::make('Statement line is already matched.');
        }

        $entry = JournalEntry::query()->findOrFail($journalEntryId);
        if ($entry->status->value !== 'posted') {
            throw BusinessRuleException::make('Only posted entries can be matched.');
        }

        $line->forceFill([
            'matched_journal_entry_id' => $entry->id,
            'matched_at' => now(),
            'matched_by' => $userId,
        ])->save();

        return $line->fresh();
    }

    public function unmatch(BankStatementLine $line): BankStatementLine
    {
        if (! $line->isMatched()) {
            throw BusinessRuleException::make('Statement line is not matched.');
        }

        if ($line->reconciliation->status === ReconciliationStatus::Reconciled) {
            throw BusinessRuleException::make('Cannot unmatch in a reconciled statement.');
        }

        $line->forceFill([
            'matched_journal_entry_id' => null,
            'matched_at' => null,
            'matched_by' => null,
        ])->save();

        return $line->fresh();
    }

    public function complete(BankReconciliation $reconciliation, int $userId): BankReconciliation
    {
        $unmatched = $reconciliation->statementLines()->whereNull('matched_journal_entry_id')->count();
        if ($unmatched > 0) {
            throw BusinessRuleException::make(
                "Cannot complete reconciliation: {$unmatched} statement line(s) unmatched."
            );
        }

        $reconciliation->forceFill([
            'status' => ReconciliationStatus::Reconciled,
            'completed_at' => now(),
            'completed_by' => $userId,
        ])->save();

        return $reconciliation->fresh();
    }

    public function unmatchedStatementLines(BankReconciliation $reconciliation): Collection
    {
        return $reconciliation->statementLines()
            ->whereNull('matched_journal_entry_id')
            ->orderBy('transaction_date')
            ->get();
    }

    /**
     * Posted journal lines hitting the bank account's GL account within the
     * statement window that have not yet been matched to a statement line.
     */
    public function unmatchedLedgerEntries(BankReconciliation $reconciliation): Collection
    {
        $account = $reconciliation->bankAccount;

        $windowStart = $reconciliation->statementLines()->min('transaction_date')
            ?? $reconciliation->statement_date;
        $windowEnd = $reconciliation->statementLines()->max('transaction_date')
            ?? $reconciliation->statement_date;

        $matchedEntryIds = $reconciliation->statementLines()
            ->whereNotNull('matched_journal_entry_id')
            ->pluck('matched_journal_entry_id');

        return JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_lines.account_id', $account->gl_account_id)
            ->whereBetween('journal_entries.entry_date', [$windowStart, $windowEnd])
            ->whereNotIn('journal_entries.id', $matchedEntryIds)
            ->orderBy('journal_entries.entry_date')
            ->get([
                'journal_entries.id as journal_entry_id',
                'journal_entries.number',
                'journal_entries.entry_date',
                'journal_entries.description',
                'journal_lines.debit',
                'journal_lines.credit',
            ]);
    }
}
