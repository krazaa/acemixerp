<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use App\Models\User;
use Modules\Sales\Models\CustomerReceipt;

class CustomerReceiptPolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('receipt.view');
    }

    public function view(User $a, CustomerReceipt $r): bool
    {
        return $a->can('receipt.view');
    }

    public function create(User $a): bool
    {
        return $a->can('receipt.create');
    }

    public function update(User $a, CustomerReceipt $r): bool
    {
        return $a->can('receipt.create') && $r->status->isEditable();
    }

    public function delete(User $a, CustomerReceipt $r): bool
    {
        return $a->can('receipt.create') && $r->status->value === 'draft';
    }

    public function submit(User $a, CustomerReceipt $r): bool
    {
        return $a->can('receipt.create') && $r->status->canSubmit();
    }

    public function approve(User $a, CustomerReceipt $r): bool
    {
        return $a->can('receipt.approve') && $r->status->canApprove();
    }

    public function reject(User $a, CustomerReceipt $r): bool
    {
        return $this->approve($a, $r);
    }

    public function post(User $a, CustomerReceipt $r): bool
    {
        return $a->can('receipt.post') && $r->status->canPost();
    }

    public function cancel(User $a, CustomerReceipt $r): bool
    {
        return $a->can('receipt.create') && $r->status->canCancel();
    }

    public function reverse(User $a, CustomerReceipt $r): bool
    {
        return $a->can('receipt.reverse') && $r->status->canReverse();
    }
}
