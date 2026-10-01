<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function view(User $actor, Setting $setting): bool
    {
        return $actor->can('settings.view');
    }

    public function update(User $actor, Setting $setting): bool
    {
        return $actor->can('settings.update');
    }
}
