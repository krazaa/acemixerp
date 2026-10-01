<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoice_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_invoice_id')
                ->constrained('sales_invoices')
                ->cascadeOnDelete();

            // Optional link to SO line and Delivery line
            $table->foreignId('sales_order_line_id')->nullable()
                ->constrained('sales_order_lines')->nullOnDelete();
            $table->foreignId('delivery_line_id')->nullable()
                ->constrained('delivery_lines')->nullOnDelete();

            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->unsignedSmallInteger('position')->default(0);

            $table->decimal('quantity', 19, 4);
            $table->decimal('unit_price', 19, 4);
            $table->decimal('discount_percent', 9, 6)->default(0);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('tax_rate', 9, 6)->default(0);
            $table->decimal('wht_tax_rate', 9, 6)->default(0);
            $table->decimal('line_subtotal', 19, 4)->default(0);
            $table->decimal('line_tax', 19, 4)->default(0);
            $table->decimal('line_wht_tax', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);

            // Cost basis for COGS posting on stock items
            $table->decimal('unit_cost', 19, 4)->default(0);
            $table->decimal('cogs_amount', 19, 4)->default(0);

            // GL accounts resolved at posting time
            $table->unsignedBigInteger('revenue_account_id')->nullable();
            $table->unsignedBigInteger('tax_account_id')->nullable();
            $table->unsignedBigInteger('cogs_account_id')->nullable();
            $table->unsignedBigInteger('inventory_account_id')->nullable();

            $table->string('description', 500)->nullable();

            $table->timestamps();

            $table->index('sales_invoice_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_lines');
    }
};
