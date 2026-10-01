<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();

            $table->string('number', 32)->unique();             // CNT-2026-000001

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->date('count_date');
            $table->string('scope', 500)->nullable();           // "All items" or "Category: Electronics"

            $table->enum('status', ['draft', 'counting', 'review', 'approved', 'posted', 'cancelled'])
                ->default('draft');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();

            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('stock_adjustment_id')->nullable()
                ->constrained('stock_adjustments')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('count_date');
            $table->index('warehouse_id');
        });

        Schema::create('stock_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained('stock_counts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();

            $table->unsignedSmallInteger('position')->default(0);

            $table->decimal('system_quantity', 19, 4);
            $table->decimal('counted_quantity', 19, 4)->nullable();
            $table->decimal('variance', 19, 4)->default(0);
            $table->decimal('unit_cost', 19, 4)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('stock_count_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_counts');
    }
};
