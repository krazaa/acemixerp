<?php

namespace Modules\Inventory\Providers;

use App\Contracts\StockLedger;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use InventoryValuationFactory;
use Modules\Inventory\Contracts\InventoryValuation;
use Modules\Inventory\Contracts\StockAdjustmentManager;
use Modules\Inventory\Contracts\StockCountManager;
use Modules\Inventory\Contracts\StockTransferManager;
use Modules\Inventory\Models\Brand;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Observers\StockBalanceNotificationObserver;
use Modules\Inventory\Policies\BrandPolicy;
use Modules\Inventory\Services\DatabaseStockLedger;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Inventory\Services\StockCountService;
use Modules\Inventory\Services\StockTransferService;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Override;

class InventoryServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Inventory';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'inventory';

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

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->singleton(
            StockTransferManager::class,
            StockTransferService::class,
        );
        $this->app->singleton(
            StockAdjustmentManager::class,
            StockAdjustmentService::class,
        );
        $this->app->singleton(
            StockCountManager::class,
            StockCountService::class,
        );

        $this->app->singleton(InventoryValuationFactory::class);

        $this->app->singleton(InventoryValuation::class, function () {
            return app(InventoryValuationFactory::class)->forCurrentOrganization();
        });
    }

    #[Override]
    public function boot(): void
    {
        parent::boot();

        Gate::policy(Brand::class, BrandPolicy::class);
        StockBalance::observe(StockBalanceNotificationObserver::class);

        $this->app->singleton(StockLedger::class, DatabaseStockLedger::class);
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
