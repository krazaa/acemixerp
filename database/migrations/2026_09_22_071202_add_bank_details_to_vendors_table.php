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
        Schema::table('vendors', function (Blueprint $table): void {
            $table->string('bank_name', 150)->nullable()->after('phone');
            $table->string('bank_branch', 150)->nullable()->after('bank_name');
            $table->text('bank_account_number')->nullable()->after('bank_branch');
            $table->string('bank_iban', 34)->nullable()->after('bank_account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropColumn(['bank_name', 'bank_branch', 'bank_account_number', 'bank_iban']);
        });
    }
};
