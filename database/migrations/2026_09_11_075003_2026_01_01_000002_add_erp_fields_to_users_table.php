<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_number', 32)->nullable()->unique()->after('id');
            $table->enum('status', ['pending', 'active', 'suspended', 'locked', 'disabled'])
                ->default('pending')
                ->after('password');
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('avatar_path')->nullable();
            $table->string('locale', 10)->default('en');
            $table->string('timezone', 64)->nullable();

            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->boolean('must_change_password')->default(false);

            $table->foreignId('department_id')->nullable()->after('status');
            $table->foreignId('designation_id')->nullable()->after('department_id');

            $table->index('status');
            $table->index('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_number', 'status', 'phone', 'avatar_path', 'locale', 'timezone',
                'failed_login_attempts', 'locked_until', 'last_login_at', 'last_login_ip',
                'two_factor_enabled', 'two_factor_secret', 'two_factor_recovery_codes',
                'password_changed_at', 'must_change_password',
                'department_id', 'designation_id',
            ]);
        });
    }
};
