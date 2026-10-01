<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();

            $table->string('code', 32)->unique();               // BOM-2026-000001
            $table->string('name', 128);
            $table->unsignedSmallInteger('revision')->default(1);

            // The finished product this BOM produces
            $table->foreignId('product_id')->constrained('items')->restrictOnDelete();

            // How much output one run produces (usually 1 unit)
            $table->decimal('output_quantity', 19, 4)->default(1);
            $table->foreignId('output_unit_id')->nullable()->constrained('units')->nullOnDelete();

            // Labour / overhead estimate (optional)
            $table->decimal('labour_cost', 19, 4)->default(0);
            $table->decimal('overhead_cost', 19, 4)->default(0);

            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'active', 'superseded', 'archived'])->default('draft');

            // Superseded-by chain when a BOM is revised
            $table->foreignId('superseded_by_id')->nullable()
                ->constrained('bill_of_materials')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
            $table->index('status');
            $table->unique(['product_id', 'revision'], 'bom_product_revision_unique');
        });

        Schema::create('bom_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_materials_id')->constrained('bill_of_materials')->cascadeOnDelete();

            $table->foreignId('component_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->decimal('quantity', 19, 4);              // per output_quantity of the BOM
            $table->decimal('scrap_percent', 9, 6)->default(0);  // waste allowance

            $table->unsignedSmallInteger('position')->default(0);
            $table->string('notes', 500)->nullable();

            $table->timestamps();

            $table->unique(['bill_of_materials_id', 'component_id'], 'bom_component_unique');
            $table->index('component_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_lines');
        Schema::dropIfExists('bill_of_materials');
    }
};
