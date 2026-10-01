<?php

namespace Modules\Inventory\Database\Factories;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\Brand;

/** @extends Factory<Brand> */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->company(), 'description' => null, 'status' => RecordStatus::Active];
    }
}
