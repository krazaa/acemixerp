<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 192);
            $table->string('short_name', 64)->nullable();

            $table->string('swift_code', 16)->nullable();
            $table->string('routing_number', 32)->nullable();
            $table->char('country', 2)->default('US');

            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();

            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('name');
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
