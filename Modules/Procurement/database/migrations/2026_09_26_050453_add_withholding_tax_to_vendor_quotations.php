<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'vendor_quotations' => ['wht_tax_total' => [19, 4]],
            'vendor_quotation_lines' => ['wht_tax_rate' => [9, 6], 'wht_tax_amount' => [19, 4]],
        ] as $name => $columns) {
            foreach ($columns as $column => [$precision, $scale]) {
                if (! Schema::hasColumn($name, $column)) {
                    Schema::table($name, function (Blueprint $table) use ($column, $precision, $scale) {
                        $table->decimal($column, $precision, $scale)->default(0);
                    });
                }
            }
        }
    }

    public function down(): void
    {
        /** Preserve withholding data, including columns that predate this migration. */
    }
};
