<?php

namespace App\Observers;

use App\Services\WorkflowNotificationService;
use Illuminate\Database\Eloquent\Model;
use Modules\Procurement\Models\VendorInvoice;

class WorkflowNotificationObserver
{
    public function __construct(private readonly WorkflowNotificationService $notifications) {}

    public function created(Model $record): void
    {
        $this->notifications->send($record);
    }

    public function updated(Model $record): void
    {
        if ($record->wasChanged('status') || ($record instanceof VendorInvoice
            && $record->wasChanged('owner_approved_at') && $record->owner_approved_at !== null)) {
            $this->notifications->send($record);
        }
    }
}
