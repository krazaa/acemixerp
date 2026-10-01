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
        Schema::table('production_order_lines', function (Blueprint $table): void {
            $table->foreignId('batch_id')->nullable()->after('component_id')->constrained('stock_batches')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_order_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('batch_id');
        });
    }
};
