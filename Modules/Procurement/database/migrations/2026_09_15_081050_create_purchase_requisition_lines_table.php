<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requisition_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_requisition_id')
                ->constrained('purchase_requisitions')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('position')->default(0);

            $table->foreignId('item_id')
                ->constrained('items')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete();

            $table->decimal('quantity', 19, 4);
            $table->decimal('estimated_unit_price', 19, 4)->default(0);
            $table->decimal('estimated_line_total', 19, 4)->default(0);

            $table->date('required_date')->nullable();
            $table->string('specification', 500)->nullable();

            // Optional link to a source document (reorder alert, project, etc.)
            $table->string('source_type', 128)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->timestamps();

            $table->index('purchase_requisition_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requisition_lines');
    }
};
