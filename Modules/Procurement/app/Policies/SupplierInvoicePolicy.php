<?php

declare(strict_types=1);

namespace Modules\Procurement\Policies;

use App\Models\User;
use Modules\Procurement\Models\SupplierInvoice;

class SupplierInvoicePolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('purchase.view');
    }

    public function view(User $a, SupplierInvoice $invoice): bool
    {
        return $this->viewAny($a);
    }

    public function create(User $a): bool
    {
        return $a->can('supplier_invoice.create');
    }

    public function update(User $a, SupplierInvoice $invoice): bool
    {
        return $a->can('supplier_invoice.create') && $invoice->status->isEditable();
    }

    public function delete(User $a, SupplierInvoice $invoice): bool
    {
        return $a->can('supplier_invoice.create') && $invoice->status->value === 'draft';
    }

    public function match(User $a, SupplierInvoice $invoice): bool
    {
        return $a->can('supplier_invoice.create')
            && $invoice->purchase_order_id !== null
            && $invoice->status->canMatch();
    }

    public function approve(User $a, SupplierInvoice $invoice): bool
    {
        return $a->hasRole('owner') && $a->can('supplier_invoice.approve')
            && ($invoice->status->canApprove()
                || ($invoice->purchase_order_id === null && $invoice->status->value === 'draft'));
    }

    public function overrideMismatch(User $a, SupplierInvoice $invoice): bool
    {
        return $a->can('purchase.approve');   // needs manager-level authority
    }

    public function reject(User $a, SupplierInvoice $invoice): bool
    {
        return $this->approve($a, $invoice);
    }

    public function post(User $a, SupplierInvoice $invoice): bool
    {
        return $a->can('supplier_invoice.post') && $invoice->status->canPost();
    }

    public function cancel(User $a, SupplierInvoice $invoice): bool
    {
        return $a->can('supplier_invoice.create') && $invoice->status->canCancel();
    }
}
