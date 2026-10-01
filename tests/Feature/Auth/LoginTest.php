<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'a@b.com',
            'password' => Hash::make('Sup3rS3cret!'),
            'status' => UserStatus::Active,
        ]);

        $response = $this->post('/login', [
            'email' => 'a@b.com', 'password' => 'Sup3rS3cret!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_disabled_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'd@b.com',
            'password' => Hash::make('Sup3rS3cret!'),
            'status' => UserStatus::Disabled,
        ]);

        $this->post('/login', ['email' => 'd@b.com', 'password' => 'Sup3rS3cret!'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_account_locks_after_five_failures(): void
    {
        $user = User::factory()->create([
            'email' => 'l@b.com',
            'password' => Hash::make('correct-pass'),
            'status' => UserStatus::Active,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'l@b.com', 'password' => 'wrong-pass']);
        }

        $user->refresh();
        $this->assertSame(UserStatus::Locked, $user->status);
        $this->assertNotNull($user->locked_until);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'rl@b.com', 'password' => 'x']);
        }
        $response = $this->post('/login', ['email' => 'rl@b.com', 'password' => 'x']);
        $response->assertSessionHasErrors('email');
    }
}
