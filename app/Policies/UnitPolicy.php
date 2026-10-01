<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    /**
     * Units are read by anyone who can see inventory, sales, or purchase
     * data — they appear in dropdowns across all three modules.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->can('inventory.view')
            || $actor->can('sales.view')
            || $actor->can('purchase.view');
    }

    public function view(User $actor, Unit $unit): bool
    {
        return $this->viewAny($actor);
    }

    /**
     * Editing a unit affects valuation and rounding across the entire system.
     * Restrict to inventory managers.
     */
    public function create(User $actor): bool
    {
        return $actor->can('inventory.adjust');
    }

    public function update(User $actor, Unit $unit): bool
    {
        return $actor->can('inventory.adjust');
    }

    public function delete(User $actor, Unit $unit): bool
    {
        return $actor->can('inventory.adjust');
    }

    public function changeStatus(User $actor, Unit $unit): bool
    {
        return $actor->can('inventory.adjust');
    }
}
