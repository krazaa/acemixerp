<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use App\Models\User;
use Modules\Sales\Models\Quotation;

class QuotationPolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('sales.view');
    }

    public function view(User $a, Quotation $q): bool
    {
        return $this->viewAny($a);
    }

    public function create(User $a): bool
    {
        return $a->can('quotation.create');
    }

    public function update(User $a, Quotation $q): bool
    {
        return $a->can('quotation.create') && $q->status->isEditable();
    }

    public function delete(User $a, Quotation $q): bool
    {
        return $a->can('quotation.create') && $q->status->value === 'draft';
    }

    public function send(User $a, Quotation $q): bool
    {
        return $a->can('quotation.create') && $q->status->canSend();
    }

    public function accept(User $a, Quotation $q): bool
    {
        return $a->can('sales.create') && $q->status->canAccept();
    }

    public function reject(User $a, Quotation $q): bool
    {
        return $a->can('sales.create') && $q->status->canReject();
    }

    public function cancel(User $a, Quotation $q): bool
    {
        return $a->can('quotation.create') && $q->status->canCancel();
    }

    public function convert(User $a, Quotation $q): bool
    {
        return $a->can('sales_order.create') && $q->status->canConvert();
    }
}
