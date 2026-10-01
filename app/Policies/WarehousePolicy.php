<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('inventory.view')
            || $actor->can('sales.view')
            || $actor->can('purchase.view');
    }

    public function view(User $actor, Warehouse $warehouse): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('inventory.adjust');
    }

    public function update(User $actor, Warehouse $warehouse): bool
    {
        return $actor->can('inventory.adjust');
    }

    public function delete(User $actor, Warehouse $warehouse): bool
    {
        return $actor->can('inventory.adjust');
    }

    public function changeStatus(User $actor, Warehouse $warehouse): bool
    {
        return $actor->can('inventory.adjust');
    }

    public function makeDefault(User $actor, Warehouse $warehouse): bool
    {
        return $actor->can('inventory.adjust');
    }
}
