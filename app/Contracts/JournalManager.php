<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\JournalEntryData;
use App\Models\JournalEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface JournalManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function createDraft(JournalEntryData $data, ?int $userId = null): JournalEntry;

    public function update(JournalEntry $entry, JournalEntryData $data): JournalEntry;

    public function submit(JournalEntry $entry, int $userId): JournalEntry;

    public function approve(JournalEntry $entry, int $userId): JournalEntry;

    public function reject(JournalEntry $entry, int $userId, string $reason): JournalEntry;

    public function cancel(JournalEntry $entry, int $userId, ?string $reason = null): JournalEntry;

    public function delete(JournalEntry $entry): void;
}
