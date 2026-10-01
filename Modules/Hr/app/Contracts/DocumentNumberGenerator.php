<?php

namespace Modules\Hr\Contracts;

interface DocumentNumberGenerator
{
    /**
     * Atomically generate the next document number.
     *
     * @param  string  $sequenceKey  e.g. 'employee', 'leave', 'payroll', 'loan'
     * @return string e.g. 'EMP-2026-000123'
     */
    public function next(string $sequenceKey, ?int $financialYearId = null): string;
}
