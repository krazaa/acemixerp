<?php

namespace Modules\Procurement\Policies;

use App\Models\User;
use Modules\Procurement\Models\PurchaseRequisition;

class PurchaseRequisitionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('purchase.view');
    }
    public function view(User $actor, PurchaseRequisition $pr): bool
    {
        return $actor->can('purchase.view')
            || $actor->id === $pr->requested_by;
    }
    public function create(User $actor): bool
    {
        return $actor->can('purchase_request.create');
    }
    public function update(User $actor, PurchaseRequisition $pr): bool
    {
        return $actor->can('purchase_request.create') && $pr->isEditable();
    }
    public function delete(User $actor, PurchaseRequisition $pr): bool
    {
        return $actor->can('purchase_request.create')
            && $pr->status->value === 'draft';
    }
    public function submit(User $actor, PurchaseRequisition $pr): bool
    {
        return $actor->can('purchase_request.create') && $pr->status->canSubmit();
    }
    public function review(User $actor, PurchaseRequisition $pr): bool
    {
        return $actor->can('purchase.approve')
            && $pr->status->value === 'submitted';
    }
    public function approve(User $actor, PurchaseRequisition $pr): bool
    {
        // Segregation of duties: the requester cannot approve their own PR.
        return $actor->can('purchase.approve')
            && $actor->id !== $pr->requested_by
            && $pr->status->canApprove();
    }
    public function reject(User $actor, PurchaseRequisition $pr): bool
    {
        return $this->approve($actor, $pr);
    }

    public function cancel(User $actor, PurchaseRequisition $pr): bool
    {
        return $actor->can('purchase.approve') && $pr->status->canCancel();
    }

    public function convertToRfq(User $actor, PurchaseRequisition $pr): bool
    {
        return $this->view($actor, $pr)
            && $actor->can('rfq.create')
            && ($actor->can('rfq.view') || $actor->can('purchase.view'))
            && $pr->status->canConvert()
            && $pr->converted_to_id === null;
    }
    public function close(User $actor, PurchaseRequisition $pr): bool
    {
        return $actor->can('purchase.approve');
    }
}
