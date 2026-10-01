<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Jobs\SendFirebaseNotification;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\ApplicationNotification;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Expense\Models\ExpenseClaim;

class ExpenseWorkflowNotifications
{
    public function send(Model $record): void
    {
        $status = $record->status instanceof BackedEnum ? $record->status->value : $record->status;
        $isClaim = $record instanceof ExpenseClaim;
        $reviewStatus = $isClaim ? 'manager_approved' : 'approved';
        $finalApproved = $isClaim ? $status === 'ceo_approved' : ($status === 'approved' && $record->owner_approved_at !== null);
        if (! $finalApproved && ! in_array($status, ['submitted', $reviewStatus, 'rejected'], true)) {
            return;
        }
        $recipients = User::query()->where('status', UserStatus::Active->value);
        if ($status === 'rejected') {
            $recipients->whereKey($record->created_by);
        } else {
            $roles = $finalApproved
                ? ['finance-manager']
                : ($status === 'submitted' ? ['operations-manager', 'operation-manager'] : ['owner', 'ceo']);
            $recipients->whereHas('roles', fn ($query) => $query->whereIn('name', $roles)->where('guard_name', 'web'));
        }
        $label = $isClaim ? 'Expense claim' : 'Vendor invoice';
        $title = $label.' '.$record->number.($finalApproved ? ' ready for finance' : ($status === 'rejected' ? ' rejected' : ' needs approval'));
        $message = $finalApproved
            ? 'Final approval is complete. Open this document for finance processing.'
            : ($status === 'rejected' ? 'Your submission was rejected. Open it to review the reason.' : 'A submission is ready for your review.');
        $url = route($isClaim ? 'expense.show' : 'expense.vendor-invoices.show', $record->getKey(), false);
        $pushEnabled = app(FirebaseMessaging::class)->enabled() && Schema::hasTable('push_subscriptions');
        $recipients->chunkById(100, function ($users) use ($title, $message, $url, $status, $pushEnabled): void {
            foreach ($users as $user) {
                $notification = new ApplicationNotification($title, $message, $url, $status === 'rejected' ? 'workflow' : 'approval');
                $notification->id = (string) Str::uuid();
                $user->notify($notification);
                if ($pushEnabled) {
                    PushSubscription::query()->where('user_id', $user->id)->each(function ($subscription) use ($user, $notification): void {
                        SendFirebaseNotification::dispatch($subscription->id, $user->id, $notification->id);
                    });
                }
            }
        });
    }
}
