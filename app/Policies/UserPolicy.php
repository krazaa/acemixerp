<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.view');
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->can('users.view') || $actor->id === $user->id;
    }

    public function create(User $actor): bool
    {
        return $actor->can('users.create');
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->can('users.update') && $actor->id !== $user->id
            || $actor->hasRole('super-admin');
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->can('users.delete') && $actor->id !== $user->id;
    }

    public function manageStatus(User $actor, User $user): bool
    {
        return $actor->can('users.manage-status') && $actor->id !== $user->id;
    }
}
