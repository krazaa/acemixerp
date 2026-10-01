<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfq_lines', function (Blueprint $table): void {
            $table->foreignId('awarded_quotation_line_id')->nullable()
                ->constrained('vendor_quotation_lines')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rfq_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('awarded_quotation_line_id');
        });
    }
};
