<?php

namespace App\Services\Accounting;

use App\Contracts\AccountingPeriodManager;
use App\Contracts\FinancialYearManager;
use App\Enums\AccountingPeriodStatus;
use App\Enums\FinancialYearStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\FinancialYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class FinancialYearService implements FinancialYearManager
{
    public function __construct(
        private readonly AccountingPeriodManager $periods,
    ) {}

    public function all(): Collection
    {
        return FinancialYear::query()->orderByDesc('start_date')->get();
    }

    public function current(): ?FinancialYear
    {
        return FinancialYear::query()->where('is_current', true)->first();
    }

    public function create(array $data): FinancialYear
    {
        return DB::transaction(function () use ($data) {
            $periodCount = (int) ($data['period_count'] ?? 12);
            if ($periodCount < 1) {
                $periodCount = 12;
            }

            $year = FinancialYear::query()->create([
                'name' => $data['name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => FinancialYearStatus::Open,
                'is_current' => false,
                'period_count' => $periodCount,
            ]);

            $this->periods->generateForYear($year, $periodCount);

            return $year->fresh(['periods']);
        });
    }

    public function markCurrent(FinancialYear $year): FinancialYear
    {
        if ($year->status === FinancialYearStatus::Closed) {
            throw BusinessRuleException::make('A closed financial year cannot be marked as current.');
        }

        return DB::transaction(function () use ($year) {
            FinancialYear::query()
                ->where('is_current', true)
                ->where('id', '!=', $year->id)
                ->update(['is_current' => false]);

            $year->forceFill(['is_current' => true])->save();

            return $year->fresh();
        });
    }

    public function beginClosing(FinancialYear $year): FinancialYear
    {
        if ($year->status !== FinancialYearStatus::Open) {
            throw BusinessRuleException::make('Only open years can begin closing.');
        }

        $year->forceFill(['status' => FinancialYearStatus::Closing])->save();

        return $year->fresh();
    }

    public function close(FinancialYear $year, ?string $notes = null): FinancialYear
    {
        return DB::transaction(function () use ($year, $notes) {
            $locked = FinancialYear::query()->lockForUpdate()->findOrFail($year->id);

            if ($locked->status === FinancialYearStatus::Closed) {
                throw BusinessRuleException::make('Year is already closed.');
            }

            // All periods must be closed before the year can close.
            $open = $locked->periods()
                ->where('status', '!=', AccountingPeriodStatus::Closed->value)
                ->count();

            if ($open > 0) {
                throw BusinessRuleException::make(
                    "Cannot close year: {$open} period(s) still open or soft-closed."
                );
            }

            // Phase 3B will add: block if unposted drafts exist.

            $locked->forceFill([
                'status' => FinancialYearStatus::Closed,
                'closed_at' => now(),
                'closed_by' => Auth::id(),
                'close_notes' => $notes,
                'is_current' => false,
            ])->save();

            return $locked->fresh();
        });
    }
}
