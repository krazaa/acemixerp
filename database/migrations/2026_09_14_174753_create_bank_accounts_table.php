<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bank_id')
                ->constrained('banks')
                ->restrictOnDelete();

            // Links to a GL account (must be a postable asset account under Bank Accounts parent)
            $table->foreignId('gl_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();

            $table->string('code', 32)->unique();
            $table->string('name', 128);

            $table->string('account_number', 64);
            $table->string('iban', 64)->nullable();
            $table->string('swift_code', 16)->nullable();

            $table->enum('account_type', ['current', 'savings', 'deposit', 'credit', 'petty_cash'])
                ->default('current');

            $table->char('currency_code', 3);

            $table->decimal('opening_balance', 19, 4)->default(0);
            $table->date('opening_balance_date')->nullable();

            $table->boolean('is_default')->default(false);

            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Account number unique per bank
            $table->unique(['bank_id', 'account_number']);

            $table->index('status');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
