<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use App\Models\User;
use Modules\Inventory\Models\StockCount;

class StockCountPolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('inventory.view');
    }

    public function view(User $a, StockCount $c): bool
    {
        return $this->viewAny($a);
    }

    public function create(User $a): bool
    {
        return $a->can('inventory.count');
    }

    public function update(User $a, StockCount $c): bool
    {
        return $a->can('inventory.count')
            && in_array($c->status->value, ['draft', 'counting'], true);
    }

    public function delete(User $a, StockCount $c): bool
    {
        return $a->can('inventory.count') && $c->status->value === 'draft';
    }

    public function start(User $a, StockCount $c): bool
    {
        return $a->can('inventory.count') && $c->status->canStart();
    }

    public function submit(User $a, StockCount $c): bool
    {
        return $a->can('inventory.count') && $c->status->canSubmit();
    }

    public function approve(User $a, StockCount $c): bool
    {
        return $a->can('inventory.count') && $c->status->canApprove();
    }

    public function post(User $a, StockCount $c): bool
    {
        return $a->can('inventory.count') && $c->status->canPost();
    }

    public function cancel(User $a, StockCount $c): bool
    {
        return $a->can('inventory.count') && $c->status->canCancel();
    }
}
