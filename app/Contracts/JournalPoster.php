<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\JournalEntryData;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\UnbalancedJournalException;
use App\Models\JournalEntry;

interface JournalPoster
{
    /**
     * Post an already-built journal entry directly. Validates:
     *   - entry is balanced
     *   - all accounts are postable
     *   - no closed periods
     *   - required system account roles are mapped
     *   - all FK dimensions resolve (cost center, department, party)
     *
     * @throws BusinessRuleException
     * @throws UnbalancedJournalException
     */
    public function post(JournalEntryData $data, array $options = []): JournalEntry;

    /**
     * Post an existing draft/approved entry.
     */
    public function postExisting(JournalEntry $entry, int $userId): JournalEntry;

    /**
     * Reverse a posted entry. Creates a new posted entry with debits and
     * credits swapped, linked to the original.
     */
    public function reverse(JournalEntry $entry, int $userId, string $reason): JournalEntry;
}
