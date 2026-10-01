<?php

namespace App\Providers;

use App\Observers\WorkflowNotificationObserver;
use App\Policies\VendorInvoicePolicy;
use App\View\Composers\NotificationMenuComposer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Procurement\Models\VendorInvoice;

class NotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(VendorInvoice::class, VendorInvoicePolicy::class);
        foreach (array_keys(config('notifications.workflows', [])) as $model) {
            if (class_exists($model)) {
                $model::observe(WorkflowNotificationObserver::class);
            }
        }
        View::composer('partials.menus._notifications-menu', NotificationMenuComposer::class);
    }
}
