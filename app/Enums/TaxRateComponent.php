<?php

namespace App\Enums;

enum TaxRateComponent: string
{
    case Output = 'output';   // sales tax (collected on behalf of authority)
    case Input = 'input';    // purchase tax (recoverable)

    public function label(): string
    {
        return match ($this) {
            self::Output => 'Output Tax (Sales)',
            self::Input => 'Input Tax (Purchases)',
        };
    }
}
