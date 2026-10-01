<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Expense\Models\ExpenseClaim;
use Modules\Procurement\Models\VendorInvoice;

class WorkflowNotificationService
{
    public function send(Model $record): void
    {
        $workflow = config('notifications.workflows')[get_class($record)] ?? null;
        if (! $workflow || ! Route::has($workflow['route']) || ! Schema::hasTable('notifications')) {
            return;
        }
        $status = $record->status instanceof BackedEnum ? $record->status->value : (string) $record->status;
        if ($status === 'draft' || $status === '') {
            return;
        }
        if ($record instanceof ExpenseClaim || $record instanceof VendorInvoice) {
            app(ExpenseWorkflowNotifications::class)->send($record);

            return;
        }
        $ability = $workflow['approvals'][$status] ?? null;
        $actorId = auth()->id() ?? $record->updated_by;
        $ownerIds = array_filter([$record->created_by, $record->submitted_by]);
        $title = $workflow['label'].' '.($record->number ?: '#'.$record->getKey());
        $url = route($workflow['route'], $record->getKey(), false);

        User::query()->where('status', UserStatus::Active->value)
            ->with(['roles.permissions', 'permissions'])->chunkById(100, function ($users) use ($record, $ability, $actorId, $ownerIds, $title, $url, $status): void {
                foreach ($users as $user) {
                    if (! $user->can('view', $record)) {
                        continue;
                    }
                    if ($ability && $user->can($ability, $record)) {
                        $user->notify(new ApplicationNotification($title.' needs approval', 'This document is ready for your review.', $url, 'approval'));
                    } elseif ($user->id !== $actorId && in_array($user->id, $ownerIds, true)) {
                        $user->notify(new ApplicationNotification($title.' updated', 'Status changed to '.Str::headline($status).'.', $url, 'workflow'));
                    }
                }
            });
    }
}
