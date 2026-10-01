<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Policies;

use App\Models\User;
use Modules\Manufacturing\Models\BillOfMaterials;

class BomPolicy
{
    public function viewAny(User $a): bool
    {
        return $a->can('bom.view');
    }

    public function view(User $a, BillOfMaterials $b): bool
    {
        return $a->can('bom.view');
    }

    public function create(User $a): bool
    {
        return $a->can('bom.manage');
    }

    public function update(User $a, BillOfMaterials $b): bool
    {
        return $a->can('bom.manage');
    }

    public function delete(User $a, BillOfMaterials $b): bool
    {
        return $a->can('bom.manage');
    }
}
