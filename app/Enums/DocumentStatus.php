<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Posted = 'posted';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Reversed, self::Rejected], true);
    }

    public function isImmutable(): bool
    {
        return in_array($this, [self::Posted, self::Completed, self::Cancelled, self::Reversed], true);
    }
}
