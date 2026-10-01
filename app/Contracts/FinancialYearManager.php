<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\FinancialYear;
use Illuminate\Support\Collection;

interface FinancialYearManager
{
    /** @return Collection<int, FinancialYear> */
    public function all(): Collection;

    public function current(): ?FinancialYear;

    public function create(array $data): FinancialYear;

    public function markCurrent(FinancialYear $year): FinancialYear;

    public function beginClosing(FinancialYear $year): FinancialYear;

    public function close(FinancialYear $year, ?string $notes = null): FinancialYear;
}
