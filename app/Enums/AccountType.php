<?php

namespace App\Enums;

enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';
    case Cogs = 'cogs';

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Asset',
            self::Liability => 'Liability',
            self::Equity => 'Equity',
            self::Revenue => 'Revenue',
            self::Expense => 'Expense',
            self::Cogs => 'Cost of Goods Sold',
        };
    }

    public function normalBalance(): NormalBalance
    {
        return match ($this) {
            self::Asset, self::Expense, self::Cogs => NormalBalance::Debit,
            self::Liability, self::Equity, self::Revenue => NormalBalance::Credit,
        };
    }

    /** Financial statement grouping. */
    public function statement(): string
    {
        return match ($this) {
            self::Asset, self::Liability, self::Equity => 'balance_sheet',
            self::Revenue, self::Expense, self::Cogs => 'profit_and_loss',
        };
    }

    public function isBalanceSheet(): bool
    {
        return $this->statement() === 'balance_sheet';
    }

    public function isProfitAndLoss(): bool
    {
        return $this->statement() === 'profit_and_loss';
    }
}
