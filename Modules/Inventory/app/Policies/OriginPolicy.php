<?php

namespace Modules\Inventory\Policies;

use App\Models\User;
use Modules\Inventory\Models\Brand;

class OriginPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('origins.view');
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->can('origins.view');
    }

    public function create(User $user): bool
    {
        return $user->can('origins.create');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->can('origins.update');
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->can('brands.delete');
    }
}
