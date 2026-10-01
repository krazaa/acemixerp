<?php

namespace App\Policies;

use App\Models\User;
use Modules\Procurement\Models\VendorInvoice;

class VendorInvoicePolicy
{
    public function view(User $user, VendorInvoice $invoice): bool
    {
        return $user->can('expense.view') || $user->can('supplier_invoice.view') || $invoice->created_by === $user->id;
    }

    public function submit(User $user, VendorInvoice $invoice): bool
    {
        return $invoice->status === 'draft' && ($user->can('expense.create') || $user->can('supplier_invoice.create'));
    }

    public function approve(User $user, VendorInvoice $invoice): bool
    {
        return $invoice->status === 'submitted' && $user->can('expense.manager_approve');
    }

    public function ownerApprove(User $user, VendorInvoice $invoice): bool
    {
        return $invoice->status === 'approved' && $invoice->owner_approved_at === null && $user->can('expense.ceo_approve');
    }

    public function reject(User $user, VendorInvoice $invoice): bool
    {
        return $this->ownerApprove($user, $invoice);
    }

    public function post(User $user, VendorInvoice $invoice): bool
    {
        return $invoice->status === 'approved' && $invoice->owner_approved_at !== null && $user->can('supplier_invoice.post');
    }
}
