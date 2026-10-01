<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('role', 64)->unique();          // matches SystemAccountRole
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_accounts');
    }
};
