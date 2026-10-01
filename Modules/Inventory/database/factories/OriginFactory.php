<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OriginFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Inventory\Models\Origin::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

