<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);

            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->decimal('quantity', 19, 4);
            $table->decimal('delivered_quantity', 19, 4)->default(0);
            $table->decimal('invoiced_quantity', 19, 4)->default(0);
            $table->decimal('returned_quantity', 19, 4)->default(0);

            $table->decimal('unit_price', 19, 4);
            $table->decimal('discount_percent', 9, 6)->default(0);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('tax_rate', 9, 6)->default(0);
            $table->decimal('wht_tax_rate', 9, 6)->default(0);
            $table->decimal('line_subtotal', 19, 4)->default(0);
            $table->decimal('line_tax', 19, 4)->default(0);
            $table->decimal('line_wht_tax', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);

            $table->string('description', 500)->nullable();

            $table->foreignId('quotation_line_id')->nullable()
                ->constrained('quotation_lines')->nullOnDelete();

            $table->timestamps();

            $table->index('sales_order_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
    }
};
