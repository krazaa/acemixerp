<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('expense_claims', 'reimbursement_bank_account_id')) {
            return;
        }

        Schema::table('expense_claims', function (Blueprint $table): void {
            $table->renameColumn('bank_account_id', 'reimbursement_bank_account_id');
        });
    }

    /**
     * Keep the original column name on rollback: the create-table migration
     * owns this column, and renaming it again would break the expense model.
     */
    public function down(): void {}
};
