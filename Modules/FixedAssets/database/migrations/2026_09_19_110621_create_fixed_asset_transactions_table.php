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
        if (Schema::hasTable('fixed_asset_transactions')) {
            return;
        }

        Schema::create('fixed_asset_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->enum('type', ['capitalization', 'depreciation', 'transfer', 'maintenance', 'revaluation', 'disposal']);
            $table->date('transaction_date');
            $table->decimal('amount', 19, 4)->default(0);
            $table->string('reference', 128)->nullable();
            $table->text('description')->nullable();
            $table->string('from_location', 192)->nullable();
            $table->string('to_location', 192)->nullable();
            $table->decimal('carrying_amount_before', 19, 4);
            $table->decimal('carrying_amount_after', 19, 4);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['asset_id', 'transaction_date']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_transactions');
    }
};
