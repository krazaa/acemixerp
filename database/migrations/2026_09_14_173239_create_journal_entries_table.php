<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            $table->string('number', 32)->unique();          // JV-2026-000001

            $table->date('entry_date');
            $table->string('reference', 64)->nullable();     // external reference

            $table->string('description', 500);

            $table->foreignId('period_id')
                ->nullable()
                ->constrained('accounting_periods')
                ->nullOnDelete();

            $table->foreignId('financial_year_id')
                ->nullable()
                ->constrained('financial_years')
                ->nullOnDelete();

            $table->char('currency_code', 3);

            $table->enum('status', ['draft', 'submitted', 'approved', 'posted', 'rejected', 'cancelled', 'reversed'])
                ->default('draft');

            // Relationship to a reversal pair
            $table->foreignId('reversed_by_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete();
            $table->foreignId('reverses_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete();

            // Posting audit
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();

            // Denormalized totals (kept in sync by the service, checked in tests)
            $table->decimal('total_debit', 19, 4)->default(0);
            $table->decimal('total_credit', 19, 4)->default(0);

            // Source document (optional) — used by AP/AR/expenses/payroll
            $table->string('source_type', 128)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            // Rejection / cancellation reason
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('entry_date');
            $table->index(['source_type', 'source_id']);
            $table->index('period_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
