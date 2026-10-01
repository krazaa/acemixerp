<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('hr.view') || $actor->can('settings.view');
    }

    public function view(User $actor, Department $department): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('hr.manage');
    }

    public function update(User $actor, Department $department): bool
    {
        return $actor->can('hr.manage');
    }

    public function delete(User $actor, Department $department): bool
    {
        return $actor->can('hr.manage');
    }

    public function changeStatus(User $actor, Department $department): bool
    {
        return $actor->can('hr.manage');
    }
}
