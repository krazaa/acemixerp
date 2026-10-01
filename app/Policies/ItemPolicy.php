<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    /**
     * Items are visible to anyone who can see inventory, sales, or purchases.
     * They are the central catalog used by all three modules.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->can('inventory.view')
            || $actor->can('sales.view')
            || $actor->can('purchase.view');
    }

    public function view(User $actor, Item $item): bool
    {
        return $this->viewAny($actor);
    }

    /**
     * Creating items is a master-data action. Grant it to inventory managers
     * (inventory.adjust) — item creation indirectly affects pricing,
     * valuation, and COGS mapping in Phase 3+.
     */
    public function create(User $actor): bool
    {
        return $actor->can('inventory.adjust');
    }

    public function update(User $actor, Item $item): bool
    {
        return $actor->can('inventory.adjust');
    }

    /**
     * Deleting an item is destructive — an item may already be referenced by
     * sales/PO/inventory documents. The service layer blocks deletion once
     * references exist; the policy adds the permission boundary.
     */
    public function delete(User $actor, Item $item): bool
    {
        return $actor->can('inventory.adjust');
    }

    public function changeStatus(User $actor, Item $item): bool
    {
        return $actor->can('inventory.adjust');
    }
}
