<?php

namespace App\Policies;

use App\Models\Bank;
use App\Models\User;

class BankPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('banking.view')
            || $actor->can('accounting.view')
            || $actor->can('settings.view');
    }

    public function view(User $actor, Bank $bank): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('banking.reconcile') || $actor->can('coa.manage');
    }

    public function update(User $actor, Bank $bank): bool
    {
        return $this->create($actor);
    }

    public function delete(User $actor, Bank $bank): bool
    {
        return $this->create($actor);
    }

    public function changeStatus(User $actor, Bank $bank): bool
    {
        return $this->create($actor);
    }
}
