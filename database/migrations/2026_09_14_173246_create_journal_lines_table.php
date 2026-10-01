<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journal_entry_id')
                ->constrained('journal_entries')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('position')->default(0);

            $table->foreignId('account_id')
                ->constrained('accounts')
                ->restrictOnDelete();

            // Debit/Credit — one is > 0, the other is 0. Enforced in service + CHECK.
            $table->decimal('debit', 19, 4)->default(0);
            $table->decimal('credit', 19, 4)->default(0);

            // Dimensions
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            $table->string('memo', 500)->nullable();

            // Source traceability per line (optional; entry-level source already covers most cases)
            $table->string('source_type', 128)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->timestamps();

            $table->index('journal_entry_id');
            $table->index('account_id');
            $table->index('cost_center_id');
            $table->index('customer_id');
            $table->index('vendor_id');
        });

        // MySQL 8 enforces a positive-only rule for the two amount columns.
        if (config('database.default') === 'mysql') {
            DB::statement('
                ALTER TABLE journal_lines
                ADD CONSTRAINT journal_lines_debit_credit_check
                CHECK (
                    (debit >= 0 AND credit >= 0)
                    AND NOT (debit > 0 AND credit > 0)
                    AND (debit > 0 OR credit > 0)
                )
            ');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
