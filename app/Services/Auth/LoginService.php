<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\UserStatus;
use App\Models\LoginAudit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class LoginService
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 900;      // 15 min

    public const LOCK_MINUTES = 30;

    public function attempt(string $email, string $password, bool $remember, string $ip, ?string $userAgent): User
    {
        $throttleKey = $this->throttleKey($email, $ip);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            $this->recordAttempt($user, $email, 'login_failed', $ip, $userAgent, 'invalid_credentials');
            $this->incrementFailedAttempts($user);
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }

        if (! $user->status->canLogin()) {
            $this->recordAttempt($user, $email, 'login_failed', $ip, $userAgent, 'status_'.$user->status->value);
            throw ValidationException::withMessages([
                'email' => "Account is {$user->status->label()}. Contact administrator.",
            ]);
        }

        if ($user->isLocked()) {
            $this->recordAttempt($user, $email, 'locked', $ip, $userAgent, 'locked');
            throw ValidationException::withMessages(['email' => 'Account is temporarily locked.']);
        }

        // Success
        Auth::login($user, $remember);
        session()->regenerate();
        $this->resetFailures($user, $ip, $userAgent);

        return $user;
    }

    public function logout(User $user, string $ip, ?string $userAgent): void
    {
        $this->recordAttempt($user, $user->email, 'logout', $ip, $userAgent);
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
    }

    private function incrementFailedAttempts(?User $user): void
    {
        if (! $user) {
            return;
        }

        $user->increment('failed_login_attempts');

        if ($user->failed_login_attempts >= self::MAX_ATTEMPTS) {
            $user->forceFill([
                'status' => UserStatus::Locked,
                'locked_until' => now()->addMinutes(self::LOCK_MINUTES),
            ])->save();
        }
    }

    private function resetFailures(User $user, string $ip, ?string $ua): void
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();

        RateLimiter::clear($this->throttleKey($user->email, $ip));
        $this->recordAttempt($user, $user->email, 'login_success', $ip, $ua);
    }

    private function recordAttempt(?User $user, string $email, string $event, string $ip, ?string $ua, ?string $reason = null): void
    {
        LoginAudit::query()->create([
            'user_id' => $user?->id,
            'email_attempted' => $email,
            'event' => $event,
            'ip_address' => $ip,
            'user_agent' => $ua ? substr($ua, 0, 500) : null,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    private function throttleKey(string $email, string $ip): string
    {
        return 'login:'.mb_strtolower($email).'|'.$ip;
    }
}
