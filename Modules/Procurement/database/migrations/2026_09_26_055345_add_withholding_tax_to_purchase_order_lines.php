<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['wht_tax_rate' => [9, 6], 'wht_line_tax' => [19, 4]] as $column => [$precision, $scale]) {
            if (! Schema::hasColumn('purchase_order_lines', $column)) {
                Schema::table('purchase_order_lines', function (Blueprint $table) use ($column, $precision, $scale): void {
                    $table->decimal($column, $precision, $scale)->default(0);
                });
            }
        }
    }

    public function down(): void
    {
        /** Preserve WHT data and columns that may predate this migration. */
    }
};
