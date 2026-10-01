<?php

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
        Schema::table('supplier_invoices', function (Blueprint $table): void {
            $table->foreignId('purchase_order_id')->nullable()->change();
        });

        Schema::table('supplier_invoice_lines', function (Blueprint $table): void {
            $table->foreignId('purchase_order_line_id')->nullable()->change();
            $table->foreignId('item_id')->nullable()->change();
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete()->after('item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_invoice_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('expense_account_id');
            $table->foreignId('purchase_order_line_id')->nullable(false)->change();
            $table->foreignId('item_id')->nullable(false)->change();
        });

        Schema::table('supplier_invoices', function (Blueprint $table): void {
            $table->foreignId('purchase_order_id')->nullable(false)->change();
        });
    }
};
