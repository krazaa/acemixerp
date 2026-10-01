<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('origins')) {
            Schema::create('origins', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code', 20)->nullable();
                $table->string('status', 16)->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('purchase_order_lines', function (Blueprint $table): void {
            $table->foreignId('origin_id')->nullable()->constrained('origins')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('origin_id');
        });
    }
};
