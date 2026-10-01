<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use App\Models\User;
use Modules\Sales\Models\SalesOrder;

class SalesOrderPolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('sales.view');
    }

    public function view(User $a, SalesOrder $o): bool
    {
        return $this->viewAny($a);
    }

    public function create(User $a): bool
    {
        return $a->can('sales_order.create');
    }

    public function update(User $a, SalesOrder $o): bool
    {
        return $a->can('sales_order.create') && $o->status->isEditable();
    }

    public function delete(User $a, SalesOrder $o): bool
    {
        return $a->can('sales_order.create') && $o->status->value === 'draft';
    }

    public function submit(User $a, SalesOrder $o): bool
    {
        return $a->can('sales_order.create') && $o->status->canSubmit();
    }

    public function approve(User $a, SalesOrder $o): bool
    {
        return $a->can('sales.approve') && $o->status->canApprove();
    }

    public function reject(User $a, SalesOrder $o): bool
    {
        return $this->approve($a, $o);
    }

    public function releaseHold(User $a, SalesOrder $o): bool
    {
        return $a->can('sales.approve') && $o->status->canReleaseHold();
    }

    public function confirm(User $a, SalesOrder $o): bool
    {
        return $a->can('sales.approve') && $o->status->canConfirm();
    }

    public function cancel(User $a, SalesOrder $o): bool
    {
        return $a->can('sales.approve') && $o->status->canCancel();
    }

    public function close(User $a, SalesOrder $o): bool
    {
        return $a->can('sales.approve');
    }
}
