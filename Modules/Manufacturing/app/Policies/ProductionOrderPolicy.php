<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Policies;

use App\Models\User;
use Modules\Manufacturing\Models\ProductionOrder;

class ProductionOrderPolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('production.view');
    }

    public function view(User $a, ProductionOrder $o): bool
    {
        return $a->can('production.view');
    }

    public function create(User $a): bool
    {
        return $a->can('production.create');
    }

    public function update(User $a, ProductionOrder $o): bool
    {
        return $a->can('production.create') && $o->status->isEditable();
    }

    public function plan(User $a, ProductionOrder $o): bool
    {
        return $a->can('production.create') && $o->status->canPlan();
    }

    public function release(User $a, ProductionOrder $o): bool
    {
        return $a->can('production.release') && $o->status->canRelease();
    }

    public function start(User $a, ProductionOrder $o): bool
    {
        return $a->can('production.execute') && $o->status->canStart();
    }

    public function complete(User $a, ProductionOrder $o): bool
    {
        return $a->can('production.execute') && $o->status->canComplete();
    }

    public function close(User $a, ProductionOrder $o): bool
    {
        return $a->can('production.release') && $o->status->canClose();
    }

    public function cancel(User $a, ProductionOrder $o): bool
    {
        return $a->can('production.release') && $o->status->canCancel();
    }
}
