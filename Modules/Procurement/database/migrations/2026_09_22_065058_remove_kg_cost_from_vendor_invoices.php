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
        if (! Schema::hasColumn('vendor_invoices', 'kg_cost')) {
            return;
        }

        Schema::table('vendor_invoices', function (Blueprint $table): void {
            $table->dropColumn('kg_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('vendor_invoices', 'kg_cost')) {
            return;
        }

        Schema::table('vendor_invoices', function (Blueprint $table): void {
            $table->decimal('kg_cost', 19, 4)->default(0)->after('billing_month');
        });
    }
};
