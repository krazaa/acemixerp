<?php

namespace Modules\Sales\Policies;

use App\Models\User;
use Modules\Sales\Models\Delivery;

class DeliveryPolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('sales.view');
    }

    public function view(User $a, Delivery $d): bool
    {
        return $this->viewAny($a);
    }

    public function create(User $a): bool
    {
        return $a->can('delivery.create');
    }

    public function update(User $a, Delivery $d): bool
    {
        return $a->can('delivery.create') && $d->status->isEditable();
    }

    public function delete(User $a, Delivery $d): bool
    {
        return $a->can('delivery.create') && $d->status->value === 'draft';
    }

    public function pick(User $a, Delivery $d): bool
    {
        return $a->can('delivery.create') && ! $d->hasBeenDispatched() && $d->status->canPick();
    }

    public function dispatch(User $a, Delivery $d): bool
    {
        return $a->can('delivery.dispatch') && ! $d->hasBeenDispatched() && $d->status->canDispatch();
    }

    public function markDelivered(User $a, Delivery $d): bool
    {
        return $a->can('delivery.dispatch')
            && ($d->status->canMarkDelivered() || $d->hasBeenDispatched());
    }

    public function close(User $a, Delivery $d): bool
    {
        return $a->can('delivery.dispatch');
    }

    public function cancel(User $a, Delivery $d): bool
    {
        return $a->can('delivery.create') && $d->status->canCancel();
    }
}
