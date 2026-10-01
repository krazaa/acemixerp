<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'RAW', 'name' => 'Raw Materials', 'description' => 'Base ingredients used in production.'],
            ['code' => 'VIT', 'name' => 'Vitamins & Supplements', 'description' => 'Vitamin and nutritional supplement inputs.'],
            ['code' => 'MIN', 'name' => 'Minerals & Additives', 'description' => 'Mineral blends, additives, and processing aids.'],
            ['code' => 'PRE', 'name' => 'Premix Ingredients', 'description' => 'Ingredients used to formulate premix products.'],
            ['code' => 'PKG', 'name' => 'Packaging Materials', 'description' => 'Bags, labels, containers, and packaging consumables.'],
            ['code' => 'FG', 'name' => 'Finished Goods', 'description' => 'Completed products available for sale.'],
            ['code' => 'SVC', 'name' => 'Services', 'description' => 'Non-inventory service items.'],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['code' => $category['code']],
                [...$category, 'status' => RecordStatus::Active],
            );
        }
    }
}
