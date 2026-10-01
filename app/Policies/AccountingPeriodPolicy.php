<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AccountingPeriod;
use App\Models\User;

class AccountingPeriodPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('accounting.view');
    }

    public function view(User $actor, AccountingPeriod $period): bool
    {
        return $this->viewAny($actor);
    }

    public function close(User $actor, AccountingPeriod $period): bool
    {
        return $actor->can('period.close');
    }

    public function reopen(User $actor, AccountingPeriod $period): bool
    {
        return $actor->can('period.reopen');
    }
}
