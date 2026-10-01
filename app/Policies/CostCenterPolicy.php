<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CostCenter;
use App\Models\User;

class CostCenterPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('settings.view') || $actor->can('accounting.view');
    }

    public function view(User $actor, CostCenter $costCenter): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        // Cost centers are an accounting artifact — finance & admins manage them.
        return $actor->can('settings.update') || $actor->can('coa.manage');
    }

    public function update(User $actor, CostCenter $costCenter): bool
    {
        return $this->create($actor);
    }

    public function delete(User $actor, CostCenter $costCenter): bool
    {
        return $this->create($actor);
    }

    public function changeStatus(User $actor, CostCenter $costCenter): bool
    {
        return $this->create($actor);
    }
}
