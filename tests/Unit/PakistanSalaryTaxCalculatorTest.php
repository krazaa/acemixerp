<?php

use Modules\Hr\Services\PakistanSalaryTaxCalculator;

it('calculates the Tax Year 2026 monthly salary withholding from annual income', function () {
    $calculator = new PakistanSalaryTaxCalculator;

    expect($calculator->monthlyTax('200000.0000', 2026))->toBe('13500.0000');
});

it('calculates the Tax Year 2027 monthly salary withholding from annual income', function () {
    $calculator = new PakistanSalaryTaxCalculator;

    expect($calculator->monthlyTax('250000.0000', 2027))->toBe('23000.0000');
});

it('rejects a tax year without a configured FBR salary schedule', function () {
    $calculator = new PakistanSalaryTaxCalculator;

    expect(fn () => $calculator->monthlyTax('100000.0000', 2028))
        ->toThrow(InvalidArgumentException::class);
});
