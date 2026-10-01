<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('roles.view');
    }

    public function view(User $actor, Role $role): bool
    {
        return $actor->can('roles.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('roles.create');
    }

    public function update(User $actor, Role $role): bool
    {
        if ($role->isSystemRole()) {
            return false; // system roles are not editable via UI
        }

        return $actor->can('roles.update');
    }

    public function delete(User $actor, Role $role): bool
    {
        if ($role->isSystemRole()) {
            return false;
        }

        return $actor->can('roles.delete');
    }
}
