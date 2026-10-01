<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('delivery_id')
                ->constrained('deliveries')
                ->cascadeOnDelete();

            $table->foreignId('sales_order_line_id')
                ->constrained('sales_order_lines')
                ->restrictOnDelete();

            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->unsignedSmallInteger('position')->default(0);

            $table->decimal('quantity', 19, 4);              // what was actually shipped
            $table->decimal('unit_price', 19, 4);            // snapshot of the SO line price

            $table->string('notes', 500)->nullable();

            $table->timestamps();

            $table->index('delivery_id');
            $table->index('sales_order_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_lines');
    }
};
