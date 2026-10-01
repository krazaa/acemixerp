<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use App\Models\User;
use Modules\Sales\Models\SalesInvoice;

class SalesInvoicePolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('invoice.view');
    }

    public function view(User $a, SalesInvoice $i): bool
    {
        return $a->can('invoice.view');
    }

    public function create(User $a): bool
    {
        return $a->can('invoice.create');
    }

    public function update(User $a, SalesInvoice $i): bool
    {
        return $a->can('invoice.create') && $i->status->isEditable();
    }

    public function delete(User $a, SalesInvoice $i): bool
    {
        return $a->can('invoice.create') && $i->status->value === 'draft';
    }

    public function match(User $a, SalesInvoice $i): bool
    {
        return $a->can('invoice.create') && $i->status->canMatch();
    }

    public function approve(User $a, SalesInvoice $i): bool
    {
        return $a->can('invoice.approve') && $i->status->canApprove();
    }

    public function reject(User $a, SalesInvoice $i): bool
    {
        return $this->approve($a, $i);
    }

    public function overrideMismatch(User $a, SalesInvoice $i): bool
    {
        return $a->can('sales.approve');
    }

    public function post(User $a, SalesInvoice $i): bool
    {
        return $a->can('invoice.post') && $i->status->canPost();
    }

    public function cancel(User $a, SalesInvoice $i): bool
    {
        return $a->can('invoice.create') && $i->status->canCancel();
    }

    public function reverse(User $a, SalesInvoice $i): bool
    {
        return $a->can('invoice.reverse') && $i->status->canReverse();
    }
}
