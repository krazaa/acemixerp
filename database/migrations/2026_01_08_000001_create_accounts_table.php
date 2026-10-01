<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            $table->string('code', 32)->unique();
            $table->string('name', 192);
            $table->string('description')->nullable();

            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense', 'cogs']);
            $table->enum('normal_balance', ['debit', 'credit']);

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            // Posting metadata
            $table->boolean('is_postable')->default(true);
            $table->boolean('is_cash')->default(false);            // direct cash account
            $table->boolean('is_bank')->default(false);            // parent of bank accounts
            $table->boolean('requires_cost_center')->default(false);
            $table->boolean('requires_department')->default(false);
            $table->boolean('requires_party')->default(false);     // AR/AP-type accounts

            // Currency (multi-currency in later phases)
            $table->char('currency_code', 3)->nullable();          // null = base currency

            // Status
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('status');
            $table->index('parent_id');
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
