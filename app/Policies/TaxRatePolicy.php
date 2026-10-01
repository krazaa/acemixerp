<?php

namespace App\Policies;

use App\Models\TaxRate;
use App\Models\User;

class TaxRatePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('accounting.view') || $actor->can('settings.view');
    }

    public function view(User $actor, TaxRate $rate): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('coa.manage');
    }

    public function update(User $actor, TaxRate $rate): bool
    {
        return $this->create($actor);
    }

    public function delete(User $actor, TaxRate $rate): bool
    {
        return $this->create($actor);
    }

    public function changeStatus(User $actor, TaxRate $rate): bool
    {
        return $this->create($actor);
    }

    public function makeDefault(User $actor, TaxRate $rate): bool
    {
        return $this->create($actor);
    }
}
