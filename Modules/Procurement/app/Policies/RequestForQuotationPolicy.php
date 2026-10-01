<?php

namespace Modules\Procurement\Policies;

use App\Models\User;
use Modules\Procurement\Models\RequestForQuotation;

class RequestForQuotationPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('purchase.view') || $actor->can('rfq.view');
    }

    public function view(User $actor, RequestForQuotation $rfq): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->can('rfq.create');
    }

    public function update(User $actor, RequestForQuotation $rfq): bool
    {
        return $actor->can('rfq.create') && $rfq->status->isEditable();
    }

    public function delete(User $actor, RequestForQuotation $rfq): bool
    {
        return $actor->can('rfq.create') && $rfq->status->value === 'draft';
    }

    public function issue(User $actor, RequestForQuotation $rfq): bool
    {
        return $actor->can('rfq.issue') && $rfq->status->canIssue();
    }

    public function award(User $actor, RequestForQuotation $rfq): bool
    {
        return ($actor->can('rfq.award') || $actor->can('rfq.issue')) && $rfq->status->canAward();
    }

    public function createPurchaseOrders(User $actor, RequestForQuotation $rfq): bool
    {
        return $actor->can('purchase_order.create')
            && $this->view($actor, $rfq)
            && $rfq->status->value === 'awarded'
            && $rfq->converted_to_id === null;
    }

    public function cancel(User $actor, RequestForQuotation $rfq): bool
    {
        return $actor->can('rfq.create') && $rfq->status->canCancel();
    }

    public function close(User $actor, RequestForQuotation $rfq): bool
    {
        return $actor->can('purchase.approve');
    }
}
