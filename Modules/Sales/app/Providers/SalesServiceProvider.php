<?php

namespace Modules\Sales\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Sales\Contracts\CreditCheckService;
use Modules\Sales\Contracts\CustomerReceiptManager;
use Modules\Sales\Contracts\DeliveryManager;
use Modules\Sales\Contracts\QuotationManager;
use Modules\Sales\Contracts\SalesInvoiceManager;
use Modules\Sales\Contracts\SalesOrderManager;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Policies\SalesReturnPolicy;
use Modules\Sales\Services\CreditCheckServiceImpl;
use Modules\Sales\Services\CustomerReceiptService;
use Modules\Sales\Services\DeliveryService;
use Modules\Sales\Services\QuotationService;
use Modules\Sales\Services\SalesInvoiceService;
use Modules\Sales\Services\SalesOrderService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SalesServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Sales';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'sales';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();
        $this->app->booted(function (): void {
            Gate::policy(SalesReturn::class, SalesReturnPolicy::class);
        });

        $this->app->singleton(
            QuotationManager::class,
            QuotationService::class,
        );

        $this->app->singleton(
            SalesOrderManager::class,
            SalesOrderService::class,
        );

        $this->app->singleton(
            CreditCheckService::class,
            CreditCheckServiceImpl::class,
        );

        $this->app->singleton(
            DeliveryManager::class,
            DeliveryService::class,
        );

        $this->app->singleton(
            SalesInvoiceManager::class,
            SalesInvoiceService::class,
        );
        $this->app->singleton(
            CustomerReceiptManager::class,
            CustomerReceiptService::class,
        );

    }

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
