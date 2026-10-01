<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('vehicle_number', 64)->nullable()->after('tracking_number');
            $table->string('driver_name', 128)->nullable()->after('vehicle_number');
            $table->string('driver_contact_number', 32)->nullable()->after('driver_name');
            $table->text('driver_cnic')->nullable()->after('driver_contact_number');
            $table->string('bilty_number', 64)->nullable()->after('driver_cnic');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_number',
                'driver_name',
                'driver_contact_number',
                'driver_cnic',
                'bilty_number',
            ]);
        });
    }
};
