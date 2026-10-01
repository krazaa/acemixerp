<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_sequences')->insertOrIgnore([
            'key' => 'goods_receipt',
            'prefix' => 'GRN',
            'pattern' => '{prefix}-{year}-{number}',
            'current_value' => 0,
            'padding' => 6,
            'reset_yearly' => true,
            'last_reset_year' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Keep document numbers that may have been issued after deployment.
    }
};
