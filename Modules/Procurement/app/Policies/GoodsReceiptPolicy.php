<?php

declare(strict_types=1);

namespace Modules\Procurement\Policies;

use App\Models\User;
use Modules\Procurement\Models\GoodsReceipt;

class GoodsReceiptPolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('purchase.view');
    }

    public function view(User $a, GoodsReceipt $grn): bool
    {
        return $this->viewAny($a);
    }

    public function create(User $a): bool
    {
        return $a->can('goods_receipt.create');
    }

    public function update(User $a, GoodsReceipt $grn): bool
    {
        return $a->can('goods_receipt.create') && $grn->status->isEditable();
    }

    public function delete(User $a, GoodsReceipt $grn): bool
    {
        return $a->can('goods_receipt.create') && $grn->status->value === 'draft';
    }

    public function post(User $a, GoodsReceipt $grn): bool
    {
        return $a->can('goods_receipt.create') && $grn->status->value === 'draft';
    }

    public function cancel(User $a, GoodsReceipt $grn): bool
    {
        return $a->can('goods_receipt.create') && $grn->status->value === 'draft';
    }
}
