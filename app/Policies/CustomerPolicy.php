<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('sales.view') || $actor->can('reports.view');
    }

    public function view(User $actor, Customer $customer): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('sales.create');
    }

    public function update(User $actor, Customer $customer): bool
    {
        return $actor->can('sales.create');
    }

    public function delete(User $actor, Customer $customer): bool
    {
        return $actor->can('sales.approve'); // delete is higher privilege
    }

    public function changeStatus(User $actor, Customer $customer): bool
    {
        return $actor->can('sales.approve');
    }
}
