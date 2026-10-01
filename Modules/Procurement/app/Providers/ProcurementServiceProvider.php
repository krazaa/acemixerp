<?php

namespace Modules\Procurement\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Procurement\Contracts\GoodsReceiptManager;
use Modules\Procurement\Contracts\PurchaseOrderManager;
use Modules\Procurement\Contracts\PurchaseRequisitionManager;
use Modules\Procurement\Contracts\RfqManager;
use Modules\Procurement\Contracts\SupplierInvoiceManager;
use Modules\Procurement\Contracts\VendorManager;
use Modules\Procurement\Contracts\VendorPaymentManager;
use Modules\Procurement\Contracts\VendorQuotationManager;
use Modules\Procurement\Services\GoodsReceiptService;
use Modules\Procurement\Services\PurchaseOrderService;
use Modules\Procurement\Services\PurchaseRequisitionService;
use Modules\Procurement\Services\RfqService;
use Modules\Procurement\Services\SupplierInvoiceService;
use Modules\Procurement\Services\ThreeWayMatchService;
use Modules\Procurement\Services\VendorPaymentService;
use Modules\Procurement\Services\VendorQuotationService;
use Modules\Procurement\Services\VendorService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ProcurementServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Procurement';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'procurement';

    public function register(): void
    {
        $this->app->singleton(
            PurchaseRequisitionManager::class,
            PurchaseRequisitionService::class,
        );

        $this->app->singleton(
            PurchaseRequisitionManager::class,
            PurchaseRequisitionService::class,
        );
        $this->app->singleton(
            RfqManager::class,
            RfqService::class,
        );
        $this->app->singleton(
            VendorQuotationManager::class,
            VendorQuotationService::class,
        );

        $this->app->singleton(
            PurchaseOrderManager::class,
            PurchaseOrderService::class,
        );
        $this->app->singleton(
            GoodsReceiptManager::class,
            GoodsReceiptService::class,
        );

        $this->app->singleton(
            SupplierInvoiceManager::class,
            SupplierInvoiceService::class,
        );
        $this->app->singleton(
            ThreeWayMatchService::class,
        );

        $this->app->singleton(
            VendorPaymentManager::class,
            VendorPaymentService::class,
        );

        $this->app->singleton(VendorManager::class, VendorService::class);

    }
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
