<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();          // KG, PCS, BOX
            $table->string('name', 64);                    // Kilogram, Pieces
            $table->string('description')->nullable();

            // Quantity precision: how many decimal places this unit uses
            $table->unsignedTinyInteger('quantity_precision')->default(2);

            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
