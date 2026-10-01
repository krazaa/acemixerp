<?php

namespace Modules\Sales\Policies;

use App\Models\User;
use Modules\Sales\Models\SalesReturn;

class SalesReturnPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('returns.view');
    }

    public function view(User $user, SalesReturn $return): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('returns.create');
    }

    public function approve(User $user, SalesReturn $return): bool
    {
        return $return->status === 'requested' && $user->can('returns.approve');
    }

    public function reject(User $user, SalesReturn $return): bool
    {
        return $return->status === 'requested' && $user->can('returns.approve');
    }

    public function receive(User $user, SalesReturn $return): bool
    {
        return $return->status === 'approved' && $user->can('returns.receive');
    }

    public function inspect(User $user, SalesReturn $return): bool
    {
        return $return->status === 'received' && $user->can('returns.inspect');
    }

    public function accept(User $user, SalesReturn $return): bool
    {
        return $return->status === 'inspected' && $user->can('returns.accept');
    }

    public function credit(User $user, SalesReturn $return): bool
    {
        return $return->status === 'accepted' && $user->can('returns.credit');
    }

    public function post(User $user, SalesReturn $return): bool
    {
        return $return->status === 'credited' && $user->can('returns.post');
    }
}
