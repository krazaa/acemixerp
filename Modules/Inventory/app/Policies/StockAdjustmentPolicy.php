<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use App\Models\User;
use Modules\Inventory\Models\StockAdjustment;

class StockAdjustmentPolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('inventory.view');
    }

    public function view(User $a, StockAdjustment $adj): bool
    {
        return $this->viewAny($a);
    }

    public function create(User $a): bool
    {
        return $a->can('inventory.adjust');
    }

    public function update(User $a, StockAdjustment $adj): bool
    {
        return $a->can('inventory.adjust') && $adj->status->isEditable();
    }

    public function delete(User $a, StockAdjustment $adj): bool
    {
        return $a->can('inventory.adjust') && $adj->status->value === 'draft';
    }

    public function submit(User $a, StockAdjustment $adj): bool
    {
        return $a->can('inventory.adjust') && $adj->status->canSubmit();
    }

    public function approve(User $a, StockAdjustment $adj): bool
    {
        return $a->can('inventory.adjust') && $adj->status->canApprove();
    }

    public function post(User $a, StockAdjustment $adj): bool
    {
        return $a->can('inventory.adjust') && $adj->status->canPost();
    }

    public function cancel(User $a, StockAdjustment $adj): bool
    {
        return $a->can('inventory.adjust') && $adj->status->canCancel();
    }
}
