<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_payments', function (Blueprint $table): void {
            $table->char('billing_month', 7)->nullable()->after('payment_date');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_payments', function (Blueprint $table): void {
            $table->dropColumn('billing_month');
        });
    }
};
