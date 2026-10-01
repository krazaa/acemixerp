<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Create a payable account for employee reimbursement claims.
     */
    public function up(): void
    {
        $currentLiabilitiesId = DB::table('accounts')->where('code', '2100')->value('id');

        DB::table('accounts')->updateOrInsert(
            ['code' => '2140'],
            [
                'name' => 'Employee Expense Payable',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'parent_id' => $currentLiabilitiesId,
                'is_postable' => true,
                'is_cash' => false,
                'is_bank' => false,
                'requires_cost_center' => false,
                'requires_department' => false,
                'requires_party' => false,
                'status' => 'active',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $accountId = DB::table('accounts')->where('code', '2140')->value('id');

        DB::table('system_accounts')->updateOrInsert(
            ['role' => 'employee_expense_payable'],
            [
                'account_id' => $accountId,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        Cache::forget('system_accounts.mappings');
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        DB::table('system_accounts')->where('role', 'employee_expense_payable')->delete();
        DB::table('accounts')->where('code', '2140')->delete();

        Cache::forget('system_accounts.mappings');
    }
};
