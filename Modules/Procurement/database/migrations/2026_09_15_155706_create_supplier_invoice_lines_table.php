<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoice_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_invoice_id')
                ->constrained('supplier_invoices')
                ->cascadeOnDelete();

            $table->foreignId('purchase_order_line_id')
                ->constrained('purchase_order_lines')
                ->restrictOnDelete();

            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();

            $table->unsignedSmallInteger('position')->default(0);

            $table->decimal('quantity', 19, 4);                    // invoiced quantity
            $table->decimal('unit_price', 19, 4);
            $table->decimal('tax_rate', 9, 6)->default(0);
            $table->decimal('line_subtotal', 19, 4)->default(0);
            $table->decimal('line_tax', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);

            // Dimension resolution chosen at post time
            $table->unsignedBigInteger('debit_account_id')->nullable();       // inventory or expense
            $table->unsignedBigInteger('input_tax_account_id')->nullable();   // input tax

            // Three-way match per line
            $table->string('match_result', 32)->nullable();          // ThreeWayMatchResult
            $table->text('match_note')->nullable();

            $table->string('description', 500)->nullable();

            $table->timestamps();

            $table->index('supplier_invoice_id');
            $table->index('purchase_order_line_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoice_lines');
    }
};
