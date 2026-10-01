<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('purchase.view') || $actor->can('reports.view');
    }

    public function view(User $actor, Vendor $vendor): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('purchase.create');
    }

    public function update(User $actor, Vendor $vendor): bool
    {
        return $actor->can('purchase.create');
    }

    public function delete(User $actor, Vendor $vendor): bool
    {
        return $actor->can('purchase.approve');
    }

    public function changeStatus(User $actor, Vendor $vendor): bool
    {
        return $actor->can('purchase.approve');
    }
}
