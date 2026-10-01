<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $actor, Organization $org): bool
    {
        return $actor->can('settings.view');
    }

    public function update(User $actor, Organization $org): bool
    {
        return $actor->can('settings.update');
    }
}
