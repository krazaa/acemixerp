<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table): void {
            $table->decimal('wht_tax_total', 19, 4)->default(0)->after('tax_total');
        });

        Schema::table('supplier_invoice_lines', function (Blueprint $table): void {
            $table->decimal('wht_rate', 9, 6)->default(0)->after('tax_rate');
            $table->decimal('line_wht_tax', 19, 4)->default(0)->after('line_tax');
            $table->decimal('line_total_wht_tax', 19, 4)->default(0)->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoice_lines', function (Blueprint $table): void {
            $table->dropColumn(['wht_rate', 'line_wht_tax', 'line_total_wht_tax']);
        });

        Schema::table('supplier_invoices', function (Blueprint $table): void {
            $table->dropColumn('wht_tax_total');
        });
    }
};
