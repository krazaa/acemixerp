<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Contracts\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Facades\Activity;

final class ActivityAuditLogger implements AuditLogger
{
    public function log(string $event, string $description, array $properties = [], ?object $subject = null): void
    {
        $logger = Activity::withProperties($properties)->event($event);

        if ($subject instanceof Model) {
            $logger->performedOn($subject);
        }

        $logger->log($description);
    }
}
