<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();

            $table->string('code', 32)->unique();
            $table->string('name', 192);
            $table->string('legal_name', 192)->nullable();
            $table->string('tax_number', 64)->nullable();
            $table->string('registration_number', 64)->nullable();

            $table->enum('status', ['pending', 'active', 'suspended', 'inactive'])
                ->default('pending');
            $table->foreignId('category_id')->nullable();

            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('website')->nullable();

            $table->char('currency_code', 3)->nullable();
            $table->unsignedSmallInteger('credit_days')->default(0);
            $table->foreignId('payment_term_id')->nullable();
            $table->foreignId('default_tax_rate_id')->nullable();
            $table->unsignedBigInteger('ap_account_id')->nullable();
            $table->boolean('is_tax_exempt')->default(false);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('name');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
