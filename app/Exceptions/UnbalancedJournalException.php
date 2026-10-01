<?php

declare(strict_types=1);

namespace App\Exceptions;

final class UnbalancedJournalException extends DomainException
{
    public static function forEntry(string $debit, string $credit): self
    {
        return new self(
            "Journal is unbalanced: debit {$debit} ≠ credit {$credit}."
        );
    }

    public function userMessage(): string
    {
        return $this->getMessage();
    }
}
