<?php

declare(strict_types=1);

namespace Modules\Expense\Policies;

use App\Models\User;
use Modules\Expense\Enums\ExpenseClaimStatus;
use Modules\Expense\Models\ExpenseClaim;

class ExpenseClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('expense.view');
    }

    public function view(User $user, ExpenseClaim $claim): bool
    {
        return $this->viewAny($user) || $claim->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('expense.create');
    }

    public function submit(User $user, ExpenseClaim $claim): bool
    {
        return $user->can('expense.create') && $claim->status === ExpenseClaimStatus::Draft;
    }

    public function update(User $user, ExpenseClaim $claim): bool
    {
        return $user->can('expense.create')
            && $claim->status === ExpenseClaimStatus::Draft
            && $claim->created_by === $user->id;
    }

    public function managerApprove(User $user, ExpenseClaim $claim): bool
    {
        if (! $user->can('expense.manager_approve') || $claim->status !== ExpenseClaimStatus::Submitted) {
            return false;
        }

        return $user->hasAnyRole(['operations-manager', 'operation-manager']) || $claim->department?->manager_id === $user->id;
    }

    public function ceoApprove(User $user, ExpenseClaim $claim): bool
    {
        return $user->can('expense.ceo_approve') && $claim->status === ExpenseClaimStatus::ManagerApproved;
    }

    public function ceoReject(User $user, ExpenseClaim $claim): bool
    {
        return $user->can('expense.ceo_approve') && $claim->status === ExpenseClaimStatus::ManagerApproved;
    }

    public function reimburse(User $user, ExpenseClaim $claim): bool
    {
        return $user->can('expense.reimburse') && $claim->status === ExpenseClaimStatus::CeoApproved;
    }
}
