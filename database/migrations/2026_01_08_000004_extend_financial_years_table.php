<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_years', function (Blueprint $table) {
            $table->text('close_notes')->nullable()->after('closed_at');
            $table->unsignedTinyInteger('period_count')->default(12)->after('close_notes');
        });
    }

    public function down(): void
    {
        Schema::table('financial_years', function (Blueprint $table) {
            $table->dropColumn(['close_notes', 'period_count']);
        });
    }
};
