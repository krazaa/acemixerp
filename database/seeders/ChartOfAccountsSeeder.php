<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\RecordStatus;
use App\Enums\SystemAccountRole;
use App\Models\Account;
use App\Models\SystemAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $accounts = [
                // Assets
                ['1000', 'Assets', AccountType::Asset, false, null],
                ['1100', 'Current Assets', AccountType::Asset, false, '1000'],
                ['1110', 'Cash on Hand', AccountType::Asset, true, '1100'],
                ['1120', 'Bank Accounts', AccountType::Asset, false, '1100'],
                ['1130', 'Accounts Receivable', AccountType::Asset, true, '1100'],
                ['1140', 'Inventory', AccountType::Asset, true, '1100'],
                ['1150', 'Input Tax Recoverable', AccountType::Asset, true, '1100'],
                ['1160', 'Expense Claims Clearing', AccountType::Asset, true, '1100'],
                ['1200', 'Fixed Assets', AccountType::Asset, false, '1000'],
                ['1210', 'Accumulated Depreciation', AccountType::Asset, true, '1200'],

                // Liabilities
                ['2000', 'Liabilities', AccountType::Liability, false, null],
                ['2100', 'Current Liabilities', AccountType::Liability, false, '2000'],
                ['2110', 'Accounts Payable', AccountType::Liability, true, '2100'],
                ['2120', 'Output Tax Payable', AccountType::Liability, true, '2100'],
                ['2130', 'Payroll Payable', AccountType::Liability, true, '2100'],
                ['2140', 'Employee Expense Payable', AccountType::Liability, true, '2100'],

                // Equity
                ['3000', 'Equity', AccountType::Equity, false, null],
                ['3100', 'Retained Earnings', AccountType::Equity, true, '3000'],

                // Revenue
                ['4000', 'Revenue', AccountType::Revenue, false, null],
                ['4100', 'Sales Revenue', AccountType::Revenue, true, '4000'],
                ['4200', 'Sales Returns', AccountType::Revenue, true, '4000'],
                ['4300', 'Sales Discounts', AccountType::Revenue, true, '4000'],

                // COGS
                ['5000', 'Cost of Goods Sold', AccountType::Cogs, false, null],
                ['5100', 'COGS — Products', AccountType::Cogs, true, '5000'],
                ['5200', 'Inventory Adjustment', AccountType::Cogs, true, '5000'],

                // Expenses
                ['6000', 'Operating Expenses', AccountType::Expense, false, null],
                ['6100', 'Payroll Expense', AccountType::Expense, true, '6000'],
                ['6200', 'Depreciation Expense', AccountType::Expense, true, '6000'],
                ['6300', 'Rent Expense', AccountType::Expense, true, '6000'],
                ['6400', 'Utilities Expense', AccountType::Expense, true, '6000'],
                ['6500', 'Food Expense', AccountType::Expense, true, '6000'],
                ['6600', 'Fuel & Toll Expense', AccountType::Expense, true, '6000'],
                ['6700', 'Vehicle Maintenance Expense', AccountType::Expense, true, '6000'],
                ['6800', 'Courier / Postage Expense', AccountType::Expense, true, '6000'],
                ['6850', 'Travel Expense', AccountType::Expense, true, '6000'],
                ['6860', 'Meals & Entertainment Expense', AccountType::Expense, true, '6000'],
                ['6900', 'Exchange Gain / Loss', AccountType::Expense, true, '6000'],
            ];

            $map = [];
            foreach ($accounts as [$code, $name, $type, $postable, $parentCode]) {
                $parent = $parentCode ? ($map[$parentCode] ?? null) : null;
                $map[$code] = Account::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'type' => $type,
                        'normal_balance' => $type->normalBalance(),
                        'parent_id' => $parent?->id,
                        'is_postable' => $postable,
                        'is_cash' => $code === '1110',
                        'is_bank' => $code === '1120',
                        'requires_party' => in_array($code, ['1130', '2110'], true),
                        'status' => RecordStatus::Active,
                    ],
                );
            }

            // System account mappings
            $systemMap = [
                [SystemAccountRole::AccountsReceivable,   '1130'],
                [SystemAccountRole::AccountsPayable,      '2110'],
                [SystemAccountRole::SalesRevenue,         '4100'],
                [SystemAccountRole::SalesReturns,         '4200'],
                [SystemAccountRole::SalesDiscounts,       '4300'],
                [SystemAccountRole::Inventory,            '1140'],
                [SystemAccountRole::Cogs,                 '5100'],
                [SystemAccountRole::InventoryAdjustment,  '5200'],
                [SystemAccountRole::InputTax,             '1150'],
                [SystemAccountRole::OutputTax,            '2120'],
                [SystemAccountRole::CashOnHand,           '1110'],
                [SystemAccountRole::RetainedEarnings,     '3100'],
                [SystemAccountRole::ExchangeGainLoss,     '6900'],
                [SystemAccountRole::PayrollExpense,       '6100'],
                [SystemAccountRole::PayrollPayable,       '2130'],
                [SystemAccountRole::EmployeeExpensePayable, '2140'],
                [SystemAccountRole::ExpenseClaimsClearing, '1160'],
                [SystemAccountRole::DepreciationExpense,  '6200'],
                [SystemAccountRole::AccumulatedDepreciation, '1210'],
            ];

            foreach ($systemMap as [$role, $code]) {
                SystemAccount::query()->updateOrCreate(
                    ['role' => $role->value],
                    ['account_id' => $map[$code]->id],
                );
            }

            cache()->forget('system_accounts.mappings');
        });
    }
}
