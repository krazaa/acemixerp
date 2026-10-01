<?php

use App\Models\VendorType;
use Database\Seeders\VendorTypesSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

pest()->extend(TestCase::class);

beforeEach(function () {
    expect(config('database.connections.sqlite.database'))->toBe(':memory:');
    Schema::create('vendor_types', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
});

it('seeds all standard vendor categories as active', function () {
    $this->seed(VendorTypesSeeder::class);

    $this->assertDatabaseCount('vendor_types', 6);
    foreach (['Supplier', 'Service Provider', 'Transport Provider', 'Contractor', 'Consultant', 'Other'] as $name) {
        $this->assertDatabaseHas('vendor_types', ['name' => $name, 'is_active' => true]);
    }
});

it('preserves existing category ids and inactive status when rerun', function () {
    $existing = VendorType::query()->create(['name' => 'Transport Provider']);
    $existing->forceFill(['is_active' => false])->save();
    VendorType::query()->create(['name' => 'Custom Category']);

    $this->seed(VendorTypesSeeder::class);
    $this->seed(VendorTypesSeeder::class);

    $this->assertDatabaseCount('vendor_types', 7);
    $this->assertDatabaseHas('vendor_types', ['id' => $existing->id, 'name' => 'Transport Provider', 'is_active' => false]);
    $this->assertDatabaseHas('vendor_types', ['name' => 'Custom Category']);
});
