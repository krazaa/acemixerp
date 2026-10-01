<?php

namespace App\Enums;

enum SystemAccountRole: string
{
    case AccountsReceivable = 'accounts_receivable';
    case AccountsPayable = 'accounts_payable';
    case SalesRevenue = 'sales_revenue';
    case SalesReturns = 'sales_returns';
    case SalesDiscounts = 'sales_discounts';
    case Inventory = 'inventory';
    case Cogs = 'cogs';
    case InventoryAdjustment = 'inventory_adjustment';
    case InputTax = 'input_tax';
    case WithholdingTaxPayable = 'withholding_tax_payable';
    case OutputTax = 'output_tax';
    case CashOnHand = 'cash_on_hand';
    case BankAccount = 'bank_account';       // default template; per-account overrides in 3C
    case RetainedEarnings = 'retained_earnings';
    case ExchangeGainLoss = 'exchange_gain_loss';
    case PayrollExpense = 'payroll_expense';
    case PayrollPayable = 'payroll_payable';
    case EmployeeExpensePayable = 'employee_expense_payable';
    case ExpenseClaimsClearing = 'expense_claims_clearing';
    case DepreciationExpense = 'depreciation_expense';
    case AccumulatedDepreciation = 'accumulated_depreciation';

    public function label(): string
    {
        return match ($this) {
            self::AccountsReceivable => 'Accounts Receivable',
            self::AccountsPayable => 'Accounts Payable',
            self::SalesRevenue => 'Sales Revenue',
            self::SalesReturns => 'Sales Returns',
            self::SalesDiscounts => 'Sales Discounts',
            self::Inventory => 'Inventory',
            self::Cogs => 'Cost of Goods Sold',
            self::InventoryAdjustment => 'Inventory Adjustment',
            self::InputTax => 'Input Tax',
            self::WithholdingTaxPayable => 'Withholding Tax Payable',
            self::OutputTax => 'Output Tax',
            self::CashOnHand => 'Cash on Hand',
            self::BankAccount => 'Bank Account (default)',
            self::RetainedEarnings => 'Retained Earnings',
            self::ExchangeGainLoss => 'Exchange Gain / Loss',
            self::PayrollExpense => 'Payroll Expense',
            self::PayrollPayable => 'Payroll Payable',
            self::EmployeeExpensePayable => 'Employee Expense Payable',
            self::ExpenseClaimsClearing => 'Expense Claims Clearing',
            self::DepreciationExpense => 'Depreciation Expense',
            self::AccumulatedDepreciation => 'Accumulated Depreciation',
        };
    }

    /** Required for the ERP to function. */
    public function isRequired(): bool
    {
        return in_array($this, [
            self::AccountsReceivable,
            self::AccountsPayable,
            self::SalesRevenue,
            self::Inventory,
            self::Cogs,
            self::InputTax,
            self::OutputTax,
            self::RetainedEarnings,
        ], true);
    }
}
