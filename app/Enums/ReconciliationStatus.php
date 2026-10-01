<?php

namespace App\Enums;

enum ReconciliationStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Reconciled = 'reconciled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Reconciled => 'Reconciled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'secondary',
            self::InProgress => 'warning',
            self::Reconciled => 'success',
        };
    }
}
