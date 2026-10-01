<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the FK constraint so we can change the column.
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropForeign(['payment_entry_id']);
        });

        // 2. Make the column nullable.
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->foreignId('payment_entry_id')
                ->nullable()
                ->change();
        });

        // 3. Re-add the FK with nullOnDelete so it plays nicely with the
        //    polymorphic pattern (allocations can survive a journal entry removal).
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->foreign('payment_entry_id')
                ->references('id')
                ->on('journal_entries')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Before narrowing the column back to NOT NULL, purge any rows that
        // only carry polymorphic payment columns and have no legacy entry.
        DB::table('payment_allocations')
            ->whereNull('payment_entry_id')
            ->delete();

        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropForeign(['payment_entry_id']);
        });

        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->foreignId('payment_entry_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->foreign('payment_entry_id')
                ->references('id')
                ->on('journal_entries')
                ->cascadeOnDelete();
        });
    }
};
