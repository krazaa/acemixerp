<?php

namespace Modules\Manufacturing\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Manufacturing\Contracts\BomManager;
use Modules\Manufacturing\Contracts\ProductionOrderManager;
use Modules\Manufacturing\Services\BomService;
use Modules\Manufacturing\Services\ProductionOrderService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ManufacturingServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Manufacturing';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'manufacturing';

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

        $this->app->singleton(BomManager::class, BomService::class);
        $this->app->singleton(ProductionOrderManager::class, ProductionOrderService::class);
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
