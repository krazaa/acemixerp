<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use App\Models\User;
use Modules\Inventory\Models\StockTransfer;

class StockTransferPolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('inventory.view');
    }

    public function view(User $a, StockTransfer $t): bool
    {
        return $this->viewAny($a);
    }

    public function create(User $a): bool
    {
        return $a->can('inventory.transfer');
    }

    public function update(User $a, StockTransfer $t): bool
    {
        return $a->can('inventory.transfer') && $t->status->isEditable();
    }

    public function delete(User $a, StockTransfer $t): bool
    {
        return $a->can('inventory.transfer') && $t->status->value === 'draft';
    }

    public function submit(User $a, StockTransfer $t): bool
    {
        return $a->can('inventory.transfer') && $t->status->canSubmit();
    }

    public function approve(User $a, StockTransfer $t): bool
    {
        return $a->can('inventory.transfer') && $t->status->canApprove();
    }

    public function dispatch(User $a, StockTransfer $t): bool
    {
        return $a->can('inventory.transfer') && $t->status->canDispatch();
    }

    public function receive(User $a, StockTransfer $t): bool
    {
        return $a->can('inventory.transfer') && $t->status->canReceive();
    }

    public function cancel(User $a, StockTransfer $t): bool
    {
        return $a->can('inventory.transfer') && $t->status->canCancel();
    }
}
