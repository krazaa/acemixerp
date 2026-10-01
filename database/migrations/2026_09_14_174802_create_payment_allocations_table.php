<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();

            // The payment/receipt journal entry
            $table->foreignId('payment_entry_id')
                ->constrained('journal_entries')
                ->cascadeOnDelete();

            // What it settles (invoice, bill, other payable)
            $table->string('allocatable_type', 128);
            $table->unsignedBigInteger('allocatable_id');

            $table->decimal('amount', 19, 4);

            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['allocatable_type', 'allocatable_id']);
            $table->index('payment_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
