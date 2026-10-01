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
        Schema::create('expense_claim_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('expense_claim_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->date('expense_date');
            $table->foreignId('expense_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('reference', 255)->nullable();
            $table->decimal('amount', 18, 4);
            $table->timestamps();

            $table->unique(['expense_claim_id', 'line_number']);
            $table->index(['expense_account_id', 'expense_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_claim_lines');
    }
};
