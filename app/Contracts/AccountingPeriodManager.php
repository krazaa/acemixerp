<?php

namespace App\Contracts;

use App\Models\AccountingPeriod;
use App\Models\FinancialYear;
use Illuminate\Support\Collection;

interface AccountingPeriodManager
{
    /** @return Collection<int, AccountingPeriod> */
    public function forYear(FinancialYear $year): Collection;

    /** @return Collection<int, AccountingPeriod> */
    public function generateForYear(FinancialYear $year, int $months = 12): Collection;

    public function close(AccountingPeriod $period, ?string $notes = null): AccountingPeriod;

    public function reopen(AccountingPeriod $period, ?string $notes = null): AccountingPeriod;

    public function assertPostable(\DateTimeInterface $date): AccountingPeriod;
}
