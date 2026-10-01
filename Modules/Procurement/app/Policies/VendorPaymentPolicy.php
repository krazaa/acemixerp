<?php

namespace Modules\Procurement\Policies;

use App\Models\User;
use Modules\Procurement\Models\VendorPayment;

class VendorPaymentPolicy
{
    /**
     * Vendor payments are read by anyone who can view payments.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->can('payment.view');
    }

    public function view(User $actor, VendorPayment $payment): bool
    {
        return $actor->can('payment.view');
    }

    /**
     * Creating a payment means the operator can authorize the source of
     * funds (bank or cash) and enter allocations. This is a bookkeeping
     * action, distinct from approving or posting.
     */
    public function create(User $actor): bool
    {
        return $actor->can('payment.create');
    }

    public function update(User $actor, VendorPayment $payment): bool
    {
        return $actor->can('payment.create')
            && $payment->status->isEditable();
    }

    public function delete(User $actor, VendorPayment $payment): bool
    {
        // Only drafts can be deleted, and only by users who can create.
        return $actor->can('payment.create')
            && $payment->status->value === 'draft';
    }

    /**
     * Submitting moves the payment from draft to the approval queue.
     * Same authority as creating.
     */
    public function submit(User $actor, VendorPayment $payment): bool
    {
        return $actor->can('payment.submit')
            && $payment->status->canSubmit();
    }

    /**
     * Approving releases the payment for posting. The service enforces
     * segregation of duties (submitter ≠ approver).
     */
    public function approve(User $actor, VendorPayment $payment): bool
    {
        return $actor->can('payment.approve')
            && $payment->status->canApprove();
    }

    public function reject(User $actor, VendorPayment $payment): bool
    {
        return $this->approve($actor, $payment);
    }

    /**
     * Posting writes to the general ledger. Higher authority than approving.
     */
    public function post(User $actor, VendorPayment $payment): bool
    {
        return $actor->can('payment.post')
            && $payment->status->canPost();
    }

    /**
     * Reversing a posted payment is a correction with financial impact.
     * Restrict to dedicated reversal authority.
     */
    public function reverse(User $actor, VendorPayment $payment): bool
    {
        return $actor->can('payment.reverse')
            && $payment->status->canReverse();
    }

    /**
     * Cancelling releases every allocation and returns the payment to a
     * terminal cancelled state. Available to operators who could have
     * cancelled by hand.
     */
    public function cancel(User $actor, VendorPayment $payment): bool
    {
        return $actor->can('payment.create')
            && $payment->status->canCancel();
    }
}
