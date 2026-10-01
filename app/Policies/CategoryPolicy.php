<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('inventory.view') || $actor->can('sales.view') || $actor->can('purchase.view');
    }

    public function view(User $actor, Category $category): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('inventory.adjust'); // master-data editing requires stock-level permission
    }

    public function update(User $actor, Category $category): bool
    {
        return $this->create($actor);
    }

    public function delete(User $actor, Category $category): bool
    {
        return $this->create($actor);
    }

    public function changeStatus(User $actor, Category $category): bool
    {
        return $this->create($actor);
    }
}
