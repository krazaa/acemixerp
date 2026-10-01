<?php

declare(strict_types=1);

namespace Modules\Hr\Services;

use InvalidArgumentException;

final class PakistanSalaryTaxCalculator
{
    public function monthlyTax(string $monthlyTaxableSalary, int $taxYear): string
    {
        if (! in_array($taxYear, [2026, 2027], true)) {
            throw new InvalidArgumentException("Pakistan salary tax slabs are not configured for Tax Year {$taxYear}.");
        }

        $annualTaxableSalary = bcmul($monthlyTaxableSalary, '12', 4);

        return bcdiv($this->annualTax($annualTaxableSalary, $taxYear), '12', 4);
    }

    private function annualTax(string $annualTaxableSalary, int $taxYear): string
    {
        if (bccomp($annualTaxableSalary, '600000', 4) <= 0) {
            return '0.0000';
        }

        if (bccomp($annualTaxableSalary, '1200000', 4) <= 0) {
            return bcmul(bcsub($annualTaxableSalary, '600000', 4), '0.01', 4);
        }

        if (bccomp($annualTaxableSalary, '2200000', 4) <= 0) {
            return bcadd('6000', bcmul(bcsub($annualTaxableSalary, '1200000', 4), '0.11', 4), 4);
        }

        if (bccomp($annualTaxableSalary, '3200000', 4) <= 0) {
            return bcadd('116000', bcmul(bcsub($annualTaxableSalary, '2200000', 4), $taxYear === 2027 ? '0.20' : '0.23', 4), 4);
        }

        if (bccomp($annualTaxableSalary, '4100000', 4) <= 0) {
            return bcadd($taxYear === 2027 ? '316000' : '346000', bcmul(bcsub($annualTaxableSalary, '3200000', 4), $taxYear === 2027 ? '0.25' : '0.30', 4), 4);
        }

        if ($taxYear === 2027 && bccomp($annualTaxableSalary, '5600000', 4) <= 0) {
            return bcadd('541000', bcmul(bcsub($annualTaxableSalary, '4100000', 4), '0.29', 4), 4);
        }

        if ($taxYear === 2027 && bccomp($annualTaxableSalary, '7000000', 4) <= 0) {
            return bcadd('976000', bcmul(bcsub($annualTaxableSalary, '5600000', 4), '0.32', 4), 4);
        }

        if ($taxYear === 2027) {
            return bcadd('1424000', bcmul(bcsub($annualTaxableSalary, '7000000', 4), '0.35', 4), 4);
        }

        return bcadd('616000', bcmul(bcsub($annualTaxableSalary, '4100000', 4), '0.35', 4), 4);
    }
}
