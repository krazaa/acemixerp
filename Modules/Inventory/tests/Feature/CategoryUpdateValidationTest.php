<?php

use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

pest()->extend(TestCase::class);

beforeEach(function () {
    config(['activitylog.enabled' => false]);
    (require database_path('migrations/2026_01_04_000001_create_categories_table.php'))->up();
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    (require database_path('migrations/2026_09_11_075003_2026_01_01_000002_add_erp_fields_to_users_table.php'))->up();
    (require database_path('migrations/2026_09_11_062704_create_permission_tables.php'))->up();
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $user->setRelation('roles', collect([new Role(['name' => 'super-admin', 'guard_name' => 'web'])]));
    $this->actingAs($user);
});

it('updates a category while keeping its existing code and name', function () {
    $category = Category::query()->create(['code' => 'FOOD', 'name' => 'Food']);

    $this->put(route('categories.update', $category), [
        'code' => 'FOOD', 'name' => 'Food', 'description' => 'Updated description',
    ])->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id, 'code' => 'FOOD', 'name' => 'Food', 'description' => 'Updated description',
    ]);
});

it('rejects another category code or name even when its id is submitted', function (string $field, string $duplicate) {
    $category = Category::query()->create(['code' => 'FOOD', 'name' => 'Food']);
    $other = Category::query()->create(['code' => 'TOOLS', 'name' => 'Tools']);

    $this->put(route('categories.update', $category), [
        'code' => 'FOOD', 'name' => 'Food', $field => $duplicate, 'id' => $other->id,
    ])->assertSessionHasErrors([$field => "The {$field} has already been taken."]);

    $this->assertDatabaseHas('categories', ['id' => $category->id, 'code' => 'FOOD', 'name' => 'Food']);
})->with(['code' => ['code', 'TOOLS'], 'name' => ['name', 'Tools']]);
