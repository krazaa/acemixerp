<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Contracts\JournalManager;
use App\Contracts\SequenceGenerator;
use App\Data\JournalEntryData;
use App\Enums\JournalEntryStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\AccountingPeriod;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class JournalService implements JournalManager
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
    ) {}

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return JournalEntry::query()
            ->with(['creator:id,name', 'poster:id,name', 'period:id,name'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(
                fn ($q) => $q->where('number', 'like', "%{$s}%")
                    ->orWhere('reference', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
            ))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('entry_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('entry_date', '<=', $d))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createDraft(JournalEntryData $data, ?int $userId = null): JournalEntry
    {
        if (count($data->lines) < 2) {
            throw BusinessRuleException::make('A journal must have at least two lines.');
        }

        return DB::transaction(function () use ($data, $userId) {
            $org = Organization::current();
            $year = FinancialYear::query()->where('is_current', true)->first();
            $period = AccountingPeriod::forDate($data->entryDate);

            $entry = JournalEntry::query()->create([
                'number' => $this->sequences->next('journal', (int) $data->entryDate->format('Y')),
                'entry_date' => $data->entryDate,
                'reference' => $data->reference,
                'description' => $data->description,
                'period_id' => $period?->id,
                'financial_year_id' => $year?->id,
                'currency_code' => $data->currencyCode ?? $org->currency_code,
                'status' => JournalEntryStatus::Draft,
                'total_debit' => $data->totalDebit(),
                'total_credit' => $data->totalCredit(),
                'notes' => $data->notes,
                'created_by' => $userId ?? Auth::id(),
            ]);

            foreach ($data->lines as $line) {
                $entry->lines()->create($line->toArray());
            }

            return $entry->fresh(['lines', 'creator']);
        });
    }

    public function update(JournalEntry $entry, JournalEntryData $data): JournalEntry
    {
        if (! $entry->isEditable()) {
            throw BusinessRuleException::make(
                "Journal {$entry->number} is {$entry->status->label()} and cannot be edited."
            );
        }

        if (count($data->lines) < 2) {
            throw BusinessRuleException::make('A journal must have at least two lines.');
        }

        return DB::transaction(function () use ($entry, $data) {
            $entry->lines()->delete();

            foreach ($data->lines as $line) {
                $entry->lines()->create($line->toArray());
            }

            $period = AccountingPeriod::forDate($data->entryDate);
            $year = FinancialYear::query()->where('is_current', true)->first();

            $entry->fill([
                'entry_date' => $data->entryDate,
                'reference' => $data->reference,
                'description' => $data->description,
                'period_id' => $period?->id,
                'financial_year_id' => $year?->id,
                'currency_code' => $data->currencyCode ?? $entry->currency_code,
                'notes' => $data->notes,
                'total_debit' => $data->totalDebit(),
                'total_credit' => $data->totalCredit(),
            ])->save();

            return $entry->fresh(['lines']);
        });
    }

    public function submit(JournalEntry $entry, int $userId): JournalEntry
    {
        if (! $entry->status->canSubmit()) {
            throw BusinessRuleException::make(
                "Journal {$entry->number} cannot be submitted from status {$entry->status->label()}."
            );
        }

        if (! $entry->isBalanced()) {
            throw BusinessRuleException::make('Journal is unbalanced and cannot be submitted.');
        }

        if ($entry->lines()->count() < 2) {
            throw BusinessRuleException::make('Journal must have at least two lines.');
        }

        $entry->forceFill([
            'status' => JournalEntryStatus::Submitted,
            'submitted_at' => now(),
            'submitted_by' => $userId,
        ])->save();

        return $entry->fresh();
    }

    public function approve(JournalEntry $entry, int $userId): JournalEntry
    {
        if (! $entry->status->canApprove()) {
            throw BusinessRuleException::make(
                "Journal {$entry->number} is not in Submitted status."
            );
        }

        if (! $entry->isBalanced()) {
            throw BusinessRuleException::make('Journal is unbalanced and cannot be approved.');
        }

        $entry->forceFill([
            'status' => JournalEntryStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $userId,
        ])->save();

        return $entry->fresh();
    }

    public function reject(JournalEntry $entry, int $userId, string $reason): JournalEntry
    {
        if ($entry->status !== JournalEntryStatus::Submitted) {
            throw BusinessRuleException::make('Only submitted journals can be rejected.');
        }

        $entry->forceFill([
            'status' => JournalEntryStatus::Rejected,
            'notes' => trim($entry->notes."\nRejected: ".$reason),
        ])->save();

        return $entry->fresh();
    }

    public function cancel(JournalEntry $entry, int $userId, ?string $reason = null): JournalEntry
    {
        if ($entry->status->isImmutable()) {
            throw BusinessRuleException::make(
                'Posted journals cannot be cancelled. Use reversal instead.'
            );
        }

        if (in_array($entry->status, [JournalEntryStatus::Draft, JournalEntryStatus::Submitted, JournalEntryStatus::Approved, JournalEntryStatus::Rejected], true) === false) {
            throw BusinessRuleException::make('This journal cannot be cancelled.');
        }

        $entry->forceFill([
            'status' => JournalEntryStatus::Cancelled,
            'notes' => $reason ? trim($entry->notes."\nCancelled: ".$reason) : $entry->notes,
        ])->save();

        return $entry->fresh();
    }

    public function delete(JournalEntry $entry): void
    {
        if ($entry->status->isImmutable()) {
            throw BusinessRuleException::make(
                'Posted journals cannot be deleted. Use reversal instead.'
            );
        }

        if ($entry->status !== JournalEntryStatus::Draft && $entry->status !== JournalEntryStatus::Rejected) {
            throw BusinessRuleException::make(
                'Only drafts and rejected journals can be deleted.'
            );
        }

        DB::transaction(fn () => $entry->delete());
    }
}
