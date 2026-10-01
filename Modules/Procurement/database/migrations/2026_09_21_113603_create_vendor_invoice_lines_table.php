<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_invoice_id')->constrained('vendor_invoices')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->decimal('tax_rate', 9, 6)->default(0);
            $table->decimal('tax_wht_rate', 9, 6)->default(0);
            $table->decimal('line_subtotal', 19, 4)->default(0);
            $table->decimal('line_tax', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4)->default(0);
            $table->decimal('line_wht_tax', 19, 4)->default(0);
            $table->decimal('line_total_wht_tax', 19, 4)->default(0);
            $table->foreignId('debit_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('input_tax_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('description', 150)->nullable();
            $table->timestamps();

            $table->index('vendor_invoice_id');
            $table->index(['vendor_invoice_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_invoice_lines');
    }
};
