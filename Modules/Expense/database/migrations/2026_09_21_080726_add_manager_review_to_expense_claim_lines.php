<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expense_claim_lines', function (Blueprint $table): void {
            $table->string('category', 50)->nullable()->after('expense_account_id');
            $table->string('manager_decision', 20)->nullable()->after('amount');
            $table->text('manager_rejection_reason')->nullable()->after('manager_decision');
            $table->foreignId('manager_reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('manager_rejection_reason');
            $table->timestamp('manager_reviewed_at')->nullable()->after('manager_reviewed_by');

            $table->index(['expense_claim_id', 'manager_decision']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_claim_lines', function (Blueprint $table): void {
            $table->dropIndex(['expense_claim_id', 'manager_decision']);
            $table->dropConstrainedForeignId('manager_reviewed_by');
            $table->dropColumn(['category', 'manager_decision', 'manager_rejection_reason', 'manager_reviewed_at']);
        });
    }
};
