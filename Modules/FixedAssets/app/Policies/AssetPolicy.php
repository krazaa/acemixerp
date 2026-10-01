<?php

namespace Modules\FixedAssets\Policies;

use App\Models\User;
use Modules\FixedAssets\Models\Asset;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('assets.view') || $user->can('accounting.view');
    }

    public function view(User $user, Asset $asset): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('assets.manage') || $user->can('coa.manage');
    }

    public function manageLifecycle(User $user, Asset $asset): bool
    {
        return ($user->can('assets.manage') || $user->can('coa.manage')) && $asset->status->value !== 'disposed';
    }
}
