<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('roles.view');
    }

    public function view(User $actor, Permission $permission): bool
    {
        return $actor->can('roles.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('roles.update');
    }

    public function update(User $actor, Permission $permission): bool
    {
        return $actor->can('roles.update');
    }

    public function delete(User $actor, Permission $permission): bool
    {
        return $actor->can('roles.update');
    }
}
