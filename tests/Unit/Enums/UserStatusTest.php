<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\UserStatus;
use PHPUnit\Framework\TestCase;

class UserStatusTest extends TestCase
{
    public function test_only_active_can_login(): void
    {
        foreach (UserStatus::cases() as $status) {
            $this->assertSame($status === UserStatus::Active, $status->canLogin(), $status->name);
        }
    }

    public function test_lifecycle_transitions(): void
    {
        $this->assertTrue(UserStatus::Pending->canTransitionTo(UserStatus::Active));
        $this->assertTrue(UserStatus::Active->canTransitionTo(UserStatus::Suspended));
        $this->assertFalse(UserStatus::Disabled->canTransitionTo(UserStatus::Active));
    }
}
