<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BankReconciliation;
use App\Models\User;

class BankReconciliationPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('banking.view');
    }

    public function view(User $actor, BankReconciliation $r): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('banking.reconcile');
    }

    public function match(User $actor, BankReconciliation $r): bool
    {
        return $actor->can('banking.reconcile');
    }

    public function complete(User $actor, BankReconciliation $r): bool
    {
        return $actor->can('banking.reconcile');
    }
}
