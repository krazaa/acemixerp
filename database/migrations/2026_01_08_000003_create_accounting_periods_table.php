<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('financial_year_id')
                ->constrained('financial_years')
                ->cascadeOnDelete();

            $table->string('name', 32);                    // "Jan 2026", "Q1 2026", etc.
            $table->date('start_date');
            $table->date('end_date');

            $table->enum('status', ['open', 'soft_closed', 'closed'])->default('open');

            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('close_notes')->nullable();

            $table->timestamps();

            $table->unique(['financial_year_id', 'start_date']);
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
