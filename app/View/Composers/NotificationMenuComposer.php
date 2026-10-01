<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class NotificationMenuComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();
        $available = $user && Schema::hasTable('notifications');
        $view->with('unreadNotificationCount', $available ? $user->unreadNotifications()->count() : 0);
        $view->with('recentNotifications', $available ? $user->notifications()->limit(6)->get() : collect());
    }
}
