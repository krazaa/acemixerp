<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();

            $table->string('number', 32)->unique();             // PRD-2026-000001

            $table->foreignId('bill_of_materials_id')
                ->constrained('bill_of_materials')
                ->restrictOnDelete();

            $table->foreignId('product_id')->constrained('items')->restrictOnDelete();

            // Quantity to produce
            $table->decimal('planned_quantity', 19, 4);
            $table->decimal('produced_quantity', 19, 4)->default(0);

            // Source warehouse (where components come from)
            $table->foreignId('source_warehouse_id')
                ->constrained('warehouses')->restrictOnDelete();

            // Destination warehouse (where finished goods go)
            $table->foreignId('destination_warehouse_id')
                ->constrained('warehouses')->restrictOnDelete();

            $table->date('scheduled_start_date')->nullable();
            $table->date('scheduled_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();

            // Ties to a sales order for make-to-order
            $table->foreignId('sales_order_id')->nullable()
                ->constrained('sales_orders')->nullOnDelete();

            $table->enum('type', ['standard', 'make_to_order', 'assembly', 'repack'])
                ->default('standard');

            $table->enum('status', [
                'draft', 'planned', 'released', 'in_progress',
                'completed', 'closed', 'cancelled',
            ])->default('draft');

            $table->text('notes')->nullable();

            // Workflow audit
            $table->timestamp('planned_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('planned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();

            // Costing (populated at completion)
            $table->decimal('materials_cost', 19, 4)->default(0);
            $table->decimal('labour_cost', 19, 4)->default(0);
            $table->decimal('overhead_cost', 19, 4)->default(0);
            $table->decimal('total_cost', 19, 4)->default(0);
            $table->decimal('unit_cost', 19, 4)->default(0);

            // GL link to the WIP → finished goods posting
            $table->foreignId('journal_entry_id')->nullable()
                ->constrained('journal_entries')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
            $table->index('status');
            $table->index('type');
            $table->index('sales_order_id');
            $table->index('scheduled_start_date');
        });

        Schema::create('production_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')
                ->constrained('production_orders')->cascadeOnDelete();

            $table->foreignId('component_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->decimal('required_quantity', 19, 4);
            $table->decimal('issued_quantity', 19, 4)->default(0);
            $table->decimal('returned_quantity', 19, 4)->default(0);
            $table->decimal('waste_quantity', 19, 4)->default(0);

            $table->decimal('unit_cost', 19, 4)->default(0);
            $table->decimal('line_cost', 19, 4)->default(0);

            $table->unsignedSmallInteger('position')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('production_order_id');
            $table->index('component_id');
        });

        Schema::create('production_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')
                ->constrained('production_orders')->cascadeOnDelete();

            $table->foreignId('product_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->decimal('quantity', 19, 4);
            $table->decimal('unit_cost', 19, 4)->default(0);
            $table->decimal('line_cost', 19, 4)->default(0);

            $table->timestamp('produced_at')->nullable();
            $table->foreignId('produced_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('production_order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_outputs');
        Schema::dropIfExists('production_order_lines');
        Schema::dropIfExists('production_orders');
    }
};
