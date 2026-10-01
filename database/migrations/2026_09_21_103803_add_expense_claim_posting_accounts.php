<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add postable operating-expense accounts used by employee reimbursement claims.
     */
    public function up(): void
    {
        $operatingExpensesId = DB::table('accounts')->where('code', '6000')->value('id');
        $timestamp = now();

        foreach ([
            ['6500', 'Food Expense'],
            ['6600', 'Fuel & Toll Expense'],
            ['6700', 'Vehicle Maintenance Expense'],
            ['6800', 'Courier / Postage Expense'],
            ['6850', 'Travel Expense'],
            ['6860', 'Meals & Entertainment Expense'],
        ] as [$code, $name]) {
            DB::table('accounts')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => 'expense',
                    'normal_balance' => 'debit',
                    'parent_id' => $operatingExpensesId,
                    'is_postable' => true,
                    'is_cash' => false,
                    'is_bank' => false,
                    'requires_cost_center' => false,
                    'requires_department' => false,
                    'requires_party' => false,
                    'status' => 'active',
                    'updated_at' => $timestamp,
                    'created_at' => $timestamp,
                ],
            );
        }
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        DB::table('accounts')
            ->whereIn('code', ['6500', '6600', '6700', '6800', '6850', '6860'])
            ->delete();
    }
};
