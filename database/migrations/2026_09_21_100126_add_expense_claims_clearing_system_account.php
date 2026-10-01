<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Create the clearing account used to defer expense recognition until reimbursement.
     */
    public function up(): void
    {
        $currentAssetsId = DB::table('accounts')->where('code', '1100')->value('id');

        DB::table('accounts')->updateOrInsert(
            ['code' => '1160'],
            [
                'name' => 'Expense Claims Clearing',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'parent_id' => $currentAssetsId,
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

        $accountId = DB::table('accounts')->where('code', '1160')->value('id');

        DB::table('system_accounts')->updateOrInsert(
            ['role' => 'expense_claims_clearing'],
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
        DB::table('system_accounts')->where('role', 'expense_claims_clearing')->delete();
        DB::table('accounts')->where('code', '1160')->delete();

        Cache::forget('system_accounts.mappings');
    }
};
