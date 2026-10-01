<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('position')->default(0);

            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->decimal('quantity', 19, 4);                  // ordered
            $table->decimal('received_quantity', 19, 4)->default(0);    // cumulative accepted
            $table->decimal('rejected_quantity', 19, 4)->default(0);    // cumulative rejected
            $table->decimal('invoiced_quantity', 19, 4)->default(0);    // cumulative invoiced

            $table->decimal('unit_price', 19, 4)->default(0);
            $table->decimal('tax_rate', 9, 6)->default(0);
            $table->decimal('line_subtotal', 19, 4)->default(0);
            $table->decimal('line_tax', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);

            $table->date('required_date')->nullable();
            $table->string('specification', 500)->nullable();

            // Link back to source line
            $table->foreignId('rfq_line_id')->nullable()->constrained('rfq_lines')->nullOnDelete();

            $table->timestamps();

            $table->index('purchase_order_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
