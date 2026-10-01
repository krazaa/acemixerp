<?php

declare(strict_types=1);

namespace App\Enums;

enum JournalEntryStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Posted = 'posted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Posted => 'Posted',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Reversed => 'Reversed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Submitted => 'info',
            self::Approved => 'primary',
            self::Posted => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'dark',
            self::Reversed => 'warning',
        };
    }

    /** Posted and reversed entries are immutable. */
    public function isImmutable(): bool
    {
        return in_array($this, [self::Posted, self::Reversed, self::Cancelled], true);
    }

    /** Only these states accept edits to lines or header. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    /** Only drafts can be submitted. */
    public function canSubmit(): bool
    {
        return $this === self::Draft || $this === self::Rejected;
    }

    /** Only submitted entries can be approved. */
    public function canApprove(): bool
    {
        return $this === self::Submitted;
    }

    /** Only approved entries can be posted directly. Drafts require approval first unless org allows. */
    public function canPost(): bool
    {
        return $this === self::Approved || $this === self::Draft;
    }

    public function canReverse(): bool
    {
        return $this === self::Posted;
    }
}
