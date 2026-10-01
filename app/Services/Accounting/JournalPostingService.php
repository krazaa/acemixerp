<?php

namespace App\Services\Accounting;

use App\Contracts\AccountingPeriodManager;
use App\Contracts\JournalPoster;
use App\Contracts\SequenceGenerator;
use App\Contracts\SystemAccountManager;
use App\Data\JournalEntryData;
use App\Data\JournalLineData;
use App\Enums\JournalEntryStatus;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\UnbalancedJournalException;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesInvoice;
use Modules\Sales\Models\SalesReturn;

final class JournalPostingService implements JournalPoster
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly AccountingPeriodManager $periods,
        private readonly SystemAccountManager $systemAccounts,
    ) {}

    public function post(JournalEntryData $data, array $options = []): JournalEntry
    {
        $this->assertSystemAccountsMapped();
        $this->assertBalanced($data);
        $this->assertPeriodOpen($data->entryDate);

        return DB::transaction(function () use ($data, $options) {
            $org = Organization::current();
            $year = FinancialYear::query()->where('is_current', true)->first();
            $period = $this->periods->assertPostable($data->entryDate);

            $entry = JournalEntry::query()->create([
                'number' => $this->sequences->next('journal', (int) $data->entryDate->format('Y')),
                'entry_date' => $data->entryDate,
                'reference' => $data->reference,
                'description' => $data->description,
                'period_id' => $period->id,
                'financial_year_id' => $year?->id,
                'currency_code' => $data->currencyCode ?? $org->currency_code,
                'status' => JournalEntryStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $options['user_id'] ?? auth()->id(),
                'created_by' => $options['user_id'] ?? auth()->id(),
                'total_debit' => $data->totalDebit(),
                'total_credit' => $data->totalCredit(),
                'source_type' => $options['source_type'] ?? null,
                'source_id' => $options['source_id'] ?? null,
                'notes' => $data->notes,
            ]);

            $this->writeLines($entry, $data->lines);

            return $entry->fresh(['lines.account', 'poster']);
        });
    }

    public function postExisting(JournalEntry $entry, int $userId): JournalEntry
    {
        return DB::transaction(function () use ($entry, $userId) {
            /** @var JournalEntry $locked */
            $locked = JournalEntry::query()->lockForUpdate()->findOrFail($entry->id);

            if (! in_array($locked->status, [JournalEntryStatus::Draft, JournalEntryStatus::Approved], true)) {
                throw BusinessRuleException::make(
                    "Journal {$locked->number} cannot be posted from status {$locked->status->label()}."
                );
            }

            $org = Organization::current();
            if ($org->require_approval_for_journal
                && $locked->status !== JournalEntryStatus::Approved) {
                throw BusinessRuleException::make(
                    'Organization policy requires journal approval before posting.'
                );
            }

            $this->assertSystemAccountsMapped();

            // Re-verify balance from persisted lines (not header totals).
            $lines = $locked->lines()->get();
            $sumDebit = '0.0000';
            $sumCredit = '0.0000';
            foreach ($lines as $line) {
                $sumDebit = bcadd($sumDebit, (string) $line->debit, 4);
                $sumCredit = bcadd($sumCredit, (string) $line->credit, 4);
            }
            if (bccomp($sumDebit, $sumCredit, 4) !== 0) {
                throw UnbalancedJournalException::forEntry($sumDebit, $sumCredit);
            }

            $period = $this->periods->assertPostable($locked->entry_date);

            $locked->forceFill([
                'status' => JournalEntryStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $userId,
                'period_id' => $period->id,
                'total_debit' => $sumDebit,
                'total_credit' => $sumCredit,
            ])->save();

            return $locked->fresh(['lines.account', 'poster']);
        });
    }

    public function reverse(JournalEntry $entry, int $userId, string $reason): JournalEntry
    {
        if (! $entry->status->canReverse()) {
            throw BusinessRuleException::make(
                "Journal {$entry->number} is {$entry->status->label()} and cannot be reversed."
            );
        }

        return DB::transaction(function () use ($entry, $userId, $reason) {
            if (in_array($entry->source_type, [SalesReturn::class, SalesCreditNote::class], true)) {
                throw BusinessRuleException::make('Sales return journals cannot be reversed independently of their inventory and credit records.');
            }
            if ($entry->source_type === SalesInvoice::class) {
                $invoice = SalesInvoice::query()->lockForUpdate()->find($entry->source_id);
                if ($invoice?->returns()->where('status', '!=', 'rejected')->exists()) {
                    throw BusinessRuleException::make('This invoice has active sales returns and cannot be reversed.');
                }
            }
            /** @var JournalEntry $locked */
            $locked = JournalEntry::query()->lockForUpdate()->findOrFail($entry->id);

            if ($locked->reversed_by_id) {
                throw BusinessRuleException::make('Journal has already been reversed.');
            }

            $period = $this->periods->assertPostable(now());

            // Build reversal with swapped debit/credit.
            $reversal = JournalEntry::query()->create([
                'number' => $this->sequences->next('journal', (int) now()->format('Y')),
                'entry_date' => now()->toDateString(),
                'reference' => $locked->reference,
                'description' => "Reversal of {$locked->number} — {$reason}",
                'period_id' => $period->id,
                'financial_year_id' => $locked->financial_year_id,
                'currency_code' => $locked->currency_code,
                'status' => JournalEntryStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $userId,
                'created_by' => $userId,
                'total_debit' => $locked->total_credit,  // swap
                'total_credit' => $locked->total_debit,   // swap
                'reverses_id' => $locked->id,
                'notes' => "Original: {$locked->number}",
            ]);

            foreach ($locked->lines as $i => $line) {
                JournalLine::query()->create([
                    'journal_entry_id' => $reversal->id,
                    'position' => $i,
                    'account_id' => $line->account_id,
                    // Swap debit/credit
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'cost_center_id' => $line->cost_center_id,
                    'department_id' => $line->department_id,
                    'customer_id' => $line->customer_id,
                    'vendor_id' => $line->vendor_id,
                    'employee_id' => $line->employee_id,
                    'memo' => 'Reversal: '.($line->memo ?? ''),
                ]);
            }

            $locked->forceFill([
                'status' => JournalEntryStatus::Reversed,
                'reversed_by_id' => $reversal->id,
            ])->save();

            return $reversal->fresh(['lines.account']);
        });
    }

    // ─── Guards ──────────────────────────────────────────────────────

    private function assertBalanced(JournalEntryData $data): void
    {
        if (count($data->lines) < 2) {
            throw BusinessRuleException::make('A journal must have at least two lines.');
        }

        if (! $data->isBalanced()) {
            throw UnbalancedJournalException::forEntry($data->totalDebit(), $data->totalCredit());
        }

        foreach ($data->lines as $i => $line) {
            if (bccomp($line->debit, '0', 4) === 0 && bccomp($line->credit, '0', 4) === 0) {
                throw BusinessRuleException::make('Line '.($i + 1).' has zero amount.');
            }
            if (bccomp($line->debit, '0', 4) > 0 && bccomp($line->credit, '0', 4) > 0) {
                throw BusinessRuleException::make(
                    'Line '.($i + 1).' cannot have both debit and credit.'
                );
            }
        }
    }

    private function assertPeriodOpen(\DateTimeInterface $date): void
    {
        $this->periods->assertPostable($date);
    }

    private function assertSystemAccountsMapped(): void
    {
        $missing = $this->systemAccounts->missingRequiredRoles();
        if (! empty($missing)) {
            throw BusinessRuleException::make(
                'Required system accounts are unmapped: '.implode(', ', $missing)
                .'. Configure them at /system-accounts.'
            );
        }
    }

    /** @param JournalLineData[] $lines */
    private function writeLines(JournalEntry $entry, array $lines): void
    {
        foreach ($lines as $line) {
            $account = Account::query()->find($line->accountId);
            if (! $account) {
                throw BusinessRuleException::make("Account ID {$line->accountId} does not exist.");
            }
            if (! $account->is_postable) {
                throw BusinessRuleException::make(
                    "Account {$account->code} ({$account->name}) is not postable."
                );
            }
            if ($account->requires_cost_center && ! $line->costCenterId) {
                throw BusinessRuleException::make(
                    "Account {$account->code} requires a cost center on every line."
                );
            }
            if ($account->requires_department && ! $line->departmentId) {
                throw BusinessRuleException::make(
                    "Account {$account->code} requires a department on every line."
                );
            }
            if ($account->requires_party && ! $line->customerId && ! $line->vendorId && ! $line->employeeId) {
                throw BusinessRuleException::make(
                    "Account {$account->code} requires a customer, vendor, or employee on every line."
                );
            }

            JournalLine::query()->create(array_merge(
                $line->toArray(),
                ['journal_entry_id' => $entry->id],
            ));
        }
    }
}
