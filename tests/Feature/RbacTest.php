<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_is_denied(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $this->actingAs($user);

        $this->assertFalse($user->can('users.create'));
    }

    public function test_user_with_permission_is_allowed(): void
    {
        Permission::findOrCreate('users.create', 'web');
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->givePermissionTo('users.create');

        $this->assertTrue($user->fresh()->can('users.create'));
    }
}
