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
            $table->foreignId('sub_account_id')->nullable()->constrained('accounts')->nullOnDelete()->after('expense_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_claim_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sub_account_id');
        });
    }
};
