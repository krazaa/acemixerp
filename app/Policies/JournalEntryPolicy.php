<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('accounting.view');
    }

    public function view(User $actor, JournalEntry $entry): bool
    {
        return $actor->can('accounting.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('journal.create');
    }

    public function update(User $actor, JournalEntry $entry): bool
    {
        return $actor->can('journal.create') && $entry->isEditable();
    }

    public function delete(User $actor, JournalEntry $entry): bool
    {
        return $actor->can('journal.create') && $entry->isEditable();
    }

    public function submit(User $actor, JournalEntry $entry): bool
    {
        return $actor->can('journal.create') && $entry->status->canSubmit();
    }

    public function approve(User $actor, JournalEntry $entry): bool
    {
        return $actor->can('journal.approve') && $entry->status->canApprove();
    }

    public function reject(User $actor, JournalEntry $entry): bool
    {
        return $actor->can('journal.approve') && $entry->status->canApprove();
    }

    public function post(User $actor, JournalEntry $entry): bool
    {
        return $actor->can('journal.post') && $entry->status->canPost();
    }

    public function reverse(User $actor, JournalEntry $entry): bool
    {
        return $actor->can('journal.reverse') && $entry->status->canReverse();
    }

    public function cancel(User $actor, JournalEntry $entry): bool
    {
        return $actor->can('journal.create') && ! $entry->status->isImmutable();
    }
}
