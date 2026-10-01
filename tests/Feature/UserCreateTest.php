<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserCreateTest extends TestCase
{
    public function test_create_page_renders_a_complete_form_without_an_existing_user(): void
    {
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_09_11_062704_create_permission_tables.php',
            '--force' => true,
        ])->assertSuccessful();

        $actor = User::factory()->make();
        $actor->setRelation('roles', new Collection);
        $actor->setRelation('permissions', new Collection);
        $this->actingAs($actor);
        $role = new Role(['name' => 'staff', 'guard_name' => 'web']);

        $view = $this->view('users.create', [
            'roles' => new Collection([$role]),
            'errors' => new ViewErrorBag,
        ]);

        $view->assertSee('Create User')
            ->assertSee('<form method="POST" action="'.route('users.store').'">', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="status"', false)
            ->assertSee('value="staff"', false)
            ->assertSee('type="submit"', false)
            ->assertDontSee('Leave blank to keep the current password.');
    }
}
