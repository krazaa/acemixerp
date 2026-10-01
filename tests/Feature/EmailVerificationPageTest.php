<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationPageTest extends TestCase
{
    public function test_unverified_user_sees_verification_actions(): void
    {
        $user = User::factory()->unverified()->make(['id' => 1]);

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertOk()->assertSee($user->email)
            ->assertSee(route('verification.send'))->assertSee(route('logout'));
    }

    public function test_resending_displays_confirmation(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->make(['id' => 1]);

        $this->actingAs($user)->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get(route('verification.notice'))->assertSee('A new verification link has been sent');
    }

    public function test_verified_user_is_redirected_to_dashboard(): void
    {
        $this->actingAs(User::factory()->make(['id' => 1]))
            ->get(route('verification.notice'))->assertRedirect(route('dashboard'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('verification.notice'))->assertRedirect(route('login'));
    }
}
