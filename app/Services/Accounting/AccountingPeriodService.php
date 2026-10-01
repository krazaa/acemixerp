<?php

namespace App\Services\Accounting;

use App\Contracts\AccountingPeriodManager;
use App\Enums\AccountingPeriodStatus;
use App\Enums\FinancialYearStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\AccountingPeriod;
use App\Models\FinancialYear;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class AccountingPeriodService implements AccountingPeriodManager
{
    public function forYear(FinancialYear $year): Collection
    {
        return AccountingPeriod::query()
            ->where('financial_year_id', $year->id)
            ->orderBy('start_date')
            ->get();
    }

    public function generateForYear(FinancialYear $year, int $months = 12): Collection
    {
        // Defensive guard: caller may pass 0, negative, or a huge number.
        if ($months < 1) {
            $months = 12;
        }
        if ($months > 24) {
            $months = 24;
        }

        if ($year->periods()->exists()) {
            throw BusinessRuleException::make(
                "Periods already exist for financial year {$year->name}."
            );
        }

        return DB::transaction(function () use ($year, $months) {
            $start = CarbonImmutable::parse($year->start_date);
            $end = CarbonImmutable::parse($year->end_date);

            $created = collect();

            for ($i = 0; $i < $months; $i++) {
                $periodStart = $start->addMonthsNoOverflow($i)->startOfMonth();
                $periodEnd = $periodStart->endOfMonth();

                if ($periodStart->greaterThan($end)) {
                    break;
                }

                if ($periodEnd->greaterThan($end)) {
                    $periodEnd = $end;
                }

                $created->push(AccountingPeriod::query()->create([
                    'financial_year_id' => $year->id,
                    'name' => $periodStart->format('M Y'),
                    'start_date' => $periodStart->toDateString(),
                    'end_date' => $periodEnd->toDateString(),
                    'status' => AccountingPeriodStatus::Open,
                ]));
            }

            return $created;
        });
    }

    public function close(AccountingPeriod $period, ?string $notes = null): AccountingPeriod
    {
        return DB::transaction(function () use ($period, $notes) {
            /** @var AccountingPeriod $locked */
            $locked = AccountingPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->id);

            if (! $locked->status->canTransitionTo(AccountingPeriodStatus::Closed)) {
                throw BusinessRuleException::make(
                    "Cannot close period {$locked->name} from status {$locked->status->label()}."
                );
            }

            $locked->forceFill([
                'status' => AccountingPeriodStatus::Closed,
                'closed_at' => now(),
                'closed_by' => Auth::id(),
                'close_notes' => $notes,
            ])->save();

            return $locked->fresh();
        });
    }

    public function reopen(AccountingPeriod $period, ?string $notes = null): AccountingPeriod
    {
        return DB::transaction(function () use ($period, $notes) {
            /** @var AccountingPeriod $locked */
            $locked = AccountingPeriod::query()
                ->with('financialYear')
                ->lockForUpdate()
                ->findOrFail($period->id);

            if ($locked->status === AccountingPeriodStatus::Open) {
                throw BusinessRuleException::make(
                    "Period {$locked->name} is already open."
                );
            }

            if ($locked->financialYear?->status === FinancialYearStatus::Closed) {
                throw BusinessRuleException::make(
                    "Cannot reopen a period belonging to a closed financial year ({$locked->financialYear->name})."
                );
            }

            // Reopening drops the period to soft_closed — posting still requires
            // an explicit open state, so adjustments are auditable.
            $locked->forceFill([
                'status' => AccountingPeriodStatus::SoftClosed,
                'closed_at' => null,
                'closed_by' => null,
                'close_notes' => $notes,
            ])->save();

            return $locked->fresh();
        });
    }

    public function assertPostable(\DateTimeInterface $date): AccountingPeriod
    {
        $period = AccountingPeriod::forDate($date);

        if (! $period) {
            throw BusinessRuleException::make(
                'No accounting period covers the date '.$date->format('Y-m-d').'.'
            );
        }

        if (! $period->status->acceptsPosting()) {
            throw BusinessRuleException::make(
                "Period {$period->name} is {$period->status->label()} and cannot accept new postings."
            );
        }

        return $period;
    }
}
