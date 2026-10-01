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
        if (Schema::hasColumn('stock_batches', 'expiry_date')) {
            return;
        }

        Schema::table('stock_batches', function (Blueprint $table): void {
            $table->date('expiry_date')->nullable()->after('number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('stock_batches', 'expiry_date')) {
            return;
        }

        Schema::table('stock_batches', function (Blueprint $table): void {
            $table->dropColumn('expiry_date');
        });
    }
};
