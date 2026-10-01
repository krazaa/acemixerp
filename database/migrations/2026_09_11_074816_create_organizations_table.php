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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_number', 64)->nullable();
            $table->string('registration_number', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->default('US');

            // Financial settings
            $table->char('currency_code', 3)->default('USD');
            $table->string('currency_symbol', 8)->default('$');
            $table->unsignedTinyInteger('currency_decimals')->default(2);
            $table->string('timezone', 64)->default('UTC');
            $table->string('date_format', 32)->default('Y-m-d');
            $table->string('fiscal_year_start_month', 2)->default('01'); // 01-12

            // Financial policy
            $table->enum('inventory_valuation_method', ['FIFO', 'WEIGHTED_AVERAGE'])
                ->default('WEIGHTED_AVERAGE');
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('require_approval_for_journal')->default(true);

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
