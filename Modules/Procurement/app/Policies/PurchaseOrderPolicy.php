<?php

declare(strict_types=1);

namespace Modules\Procurement\Policies;

use App\Models\User;
use Modules\Procurement\Models\PurchaseOrder;

class PurchaseOrderPolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('purchase.view');
    }

    public function view(User $a, PurchaseOrder $po): bool
    {
        return $this->viewAny($a);
    }

    public function create(User $a): bool
    {
        return $a->can('purchase_order.create');
    }

    public function update(User $a, PurchaseOrder $po): bool
    {
        return $a->can('purchase_order.create') && $po->status->isEditable();
    }

    public function delete(User $a, PurchaseOrder $po): bool
    {
        return $a->can('purchase_order.create') && $po->status->value === 'draft';
    }

    public function submit(User $a, PurchaseOrder $po): bool
    {
        return $a->can('purchase_order.create') && $po->status->canSubmit();
    }

    public function approve(User $a, PurchaseOrder $po): bool
    {
        return $a->can('purchase.approve') && $po->status->canApprove();
    }

    public function reject(User $a, PurchaseOrder $po): bool
    {
        return $this->approve($a, $po);
    }

    public function issue(User $a, PurchaseOrder $po): bool
    {
        return $a->can('purchase_order.issue') && $po->status->canIssue();
    }

    public function cancel(User $a, PurchaseOrder $po): bool
    {
        return $a->can('purchase.approve') && $po->status->canCancel();
    }

    public function close(User $a, PurchaseOrder $po): bool
    {
        return $a->can('purchase.approve');
    }
}
