<?php

declare(strict_types=1);

namespace App\Enums;

enum BankAccountType: string
{
    case Current = 'current';
    case Savings = 'savings';
    case Deposit = 'deposit';
    case Credit = 'credit';     // overdraft / credit line
    case PettyCash = 'petty_cash'; // cash drawer

    public function label(): string
    {
        return match ($this) {
            self::Current => 'Current Account',
            self::Savings => 'Savings Account',
            self::Deposit => 'Deposit Account',
            self::Credit => 'Credit Line',
            self::PettyCash => 'Petty Cash',
        };
    }
}
