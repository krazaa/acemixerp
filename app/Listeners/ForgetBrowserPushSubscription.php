<?php

namespace App\Listeners;

use App\Models\PushSubscription;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Schema;

class ForgetBrowserPushSubscription
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        $device = request()->cookie('push_device');
        if ($event->user && $device && Schema::hasTable('push_subscriptions')) {
            PushSubscription::query()->where('user_id', $event->user->getAuthIdentifier())
                ->where('device_id', $device)->delete();
        }
    }
}
