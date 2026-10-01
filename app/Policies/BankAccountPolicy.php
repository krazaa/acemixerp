<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BankAccount;
use App\Models\User;

class BankAccountPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('banking.view');
    }

    public function view(User $actor, BankAccount $account): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('banking.reconcile');
    }

    public function update(User $actor, BankAccount $account): bool
    {
        return $this->create($actor);
    }

    public function delete(User $actor, BankAccount $account): bool
    {
        return $this->create($actor);
    }

    public function changeStatus(User $actor, BankAccount $account): bool
    {
        return $this->create($actor);
    }

    public function makeDefault(User $actor, BankAccount $account): bool
    {
        return $this->create($actor);
    }
}
