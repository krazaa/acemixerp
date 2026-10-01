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
            $table->decimal('manager_deduction_amount', 18, 4)->default(0)->after('manager_decision');
            $table->text('manager_deduction_reason')->nullable()->after('manager_deduction_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_claim_lines', function (Blueprint $table): void {
            $table->dropColumn(['manager_deduction_amount', 'manager_deduction_reason']);
        });
    }
};
