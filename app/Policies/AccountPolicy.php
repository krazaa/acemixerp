<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('coa.view') || $actor->can('accounting.view');
    }

    public function view(User $actor, Account $account): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('coa.manage');
    }

    public function update(User $actor, Account $account): bool
    {
        return $actor->can('coa.manage');
    }

    public function delete(User $actor, Account $account): bool
    {
        return $actor->can('coa.manage');
    }

    public function changeStatus(User $actor, Account $account): bool
    {
        return $actor->can('coa.manage');
    }
}
