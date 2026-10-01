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
        Schema::table('fixed_asset_transactions', function (Blueprint $table) {
            $table->foreign('asset_id')->references('id')->on('fixed_assets')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fixed_asset_transactions', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
        });
    }
};
