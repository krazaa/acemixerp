<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Designation;
use App\Models\User;

class DesignationPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('hr.view') || $actor->can('settings.view');
    }

    public function view(User $actor, Designation $designation): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('hr.manage');
    }

    public function update(User $actor, Designation $designation): bool
    {
        return $actor->can('hr.manage');
    }

    public function delete(User $actor, Designation $designation): bool
    {
        return $actor->can('hr.manage');
    }

    public function changeStatus(User $actor, Designation $designation): bool
    {
        return $actor->can('hr.manage');
    }
}
