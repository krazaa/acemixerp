<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FinancialYear;
use App\Models\User;

class FinancialYearPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('accounting.view');
    }

    public function view(User $actor, FinancialYear $year): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('year.create');
    }

    public function update(User $actor, FinancialYear $year): bool
    {
        return $actor->can('year.create');
    }

    public function markCurrent(User $actor, FinancialYear $year): bool
    {
        return $actor->can('year.close');
    }

    public function close(User $actor, FinancialYear $year): bool
    {
        return $actor->can('year.close');
    }
}
