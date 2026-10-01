<?php

declare(strict_types=1);

use App\Models\JournalEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            // Keep payment_entry_id for backwards compatibility with any
            // existing AR-side allocations, but add a polymorphic pair for
            // vendor payments.
            $table->string('payment_type', 128)->nullable()->after('id');
            $table->unsignedBigInteger('payment_id')->nullable()->after('payment_type');
            $table->index(['payment_type', 'payment_id'], 'payment_alloc_poly_idx');
        });

        // Backfill the polymorphic pair for any existing rows.
        DB::table('payment_allocations')
            ->whereNotNull('payment_entry_id')
            ->update([
                'payment_type' => JournalEntry::class,
                'payment_id' => DB::raw('payment_entry_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropIndex('payment_alloc_poly_idx');
            $table->dropColumn(['payment_type', 'payment_id']);
        });
    }
};
