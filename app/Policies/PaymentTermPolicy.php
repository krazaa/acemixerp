<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PaymentTerm;
use App\Models\User;

class PaymentTermPolicy
{
    /**
     * Payment terms are read by anyone who can touch accounting, sales,
     * or purchasing — they appear on invoices, POs, and vendor/customer
     * profiles.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->can('accounting.view')
            || $actor->can('sales.view')
            || $actor->can('purchase.view')
            || $actor->can('settings.view');
    }

    public function view(User $actor, PaymentTerm $term): bool
    {
        return $this->viewAny($actor);
    }

    /**
     * Creating a payment term affects due-date computation across the
     * entire system. Restrict to chart-of-accounts managers — the same
     * role that owns tax rates and account mappings.
     */
    public function create(User $actor): bool
    {
        return $actor->can('coa.manage');
    }

    public function update(User $actor, PaymentTerm $term): bool
    {
        return $this->create($actor);
    }

    public function delete(User $actor, PaymentTerm $term): bool
    {
        return $this->create($actor);
    }

    public function changeStatus(User $actor, PaymentTerm $term): bool
    {
        return $this->create($actor);
    }

    /**
     * Flipping the default term affects every new invoice/PO that
     * doesn't explicitly override it — higher blast radius than
     * editing a single term.
     */
    public function makeDefault(User $actor, PaymentTerm $term): bool
    {
        return $actor->can('coa.manage');
    }
}
