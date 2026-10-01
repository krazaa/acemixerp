<?php

namespace App\Providers;

use App\Contracts\AccountingPeriodManager;
use App\Contracts\AccountManager;
use App\Contracts\AgingService;
use App\Contracts\AuditLogger;
use App\Contracts\BankAccountManager;
use App\Contracts\BankManager;
use App\Contracts\BankReconciliationManager;
use App\Contracts\CategoryManager;
use App\Contracts\CostCenterManager;
use App\Contracts\CustomerManager;
use App\Contracts\DepartmentManager;
use App\Contracts\DesignationManager;
use App\Contracts\FinancialYearManager;
use App\Contracts\ItemManager;
use App\Contracts\JournalManager;
use App\Contracts\JournalPoster;
use App\Contracts\LedgerService;
use App\Contracts\OrganizationUpdater;
use App\Contracts\PartyLedgerService;
use App\Contracts\PaymentTermManager;
use App\Contracts\PermissionManager;
use App\Contracts\RoleManager;
use App\Contracts\SequenceGenerator;
use App\Contracts\SettingRepository;
use App\Contracts\StockLedger;
use App\Contracts\SystemAccountManager;
use App\Contracts\TaxRateManager;
use App\Contracts\UnitManager;
use App\Contracts\UserManager;
use App\Contracts\VendorManager;
use App\Contracts\WarehouseManager;
use App\Models\User;
use App\Services\Accounting\AccountingPeriodService;
use App\Services\Accounting\AccountService;
use App\Services\Accounting\AgingServiceImpl;
use App\Services\Accounting\BankAccountService;
use App\Services\Accounting\BankReconciliationService;
use App\Services\Accounting\FinancialYearService;
use App\Services\Accounting\GeneralLedgerService;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\PartyLedgerServiceImpl;
use App\Services\Accounting\SystemAccountService;
use App\Services\Audit\ActivityAuditLogger;
use App\Services\Inventory\NullStockLedger;
use App\Services\MasterData\BankService;
use App\Services\MasterData\CategoryService;
use App\Services\MasterData\CostCenterService;
use App\Services\MasterData\CustomerService;
use App\Services\MasterData\DepartmentService;
use App\Services\MasterData\DesignationService;
use App\Services\MasterData\ItemService;
use App\Services\MasterData\PaymentTermService;
use App\Services\MasterData\TaxRateService;
use App\Services\MasterData\UnitService;
use App\Services\MasterData\VendorService;
use App\Services\MasterData\WarehouseService;
use App\Services\Organization\DatabaseOrganizationUpdater;
use App\Services\Rbac\PermissionService;
use App\Services\Rbac\RoleService;
use App\Services\Rbac\UserService;
use App\Services\Sequence\DatabaseSequenceGenerator;
use App\Services\Settings\DatabaseSettingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Procurement\Models\SupplierInvoice;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SequenceGenerator::class, DatabaseSequenceGenerator::class);
        $this->app->singleton(SettingRepository::class, DatabaseSettingRepository::class);
        $this->app->singleton(AuditLogger::class, ActivityAuditLogger::class);
        $this->app->singleton(OrganizationUpdater::class, DatabaseOrganizationUpdater::class); // ← add

        $this->app->singleton(UserManager::class, UserService::class);
        $this->app->singleton(RoleManager::class, RoleService::class);
        $this->app->singleton(PermissionManager::class, PermissionService::class);
        $this->app->singleton(DepartmentManager::class, DepartmentService::class);
        $this->app->singleton(DesignationManager::class, DesignationService::class);
        $this->app->singleton(CostCenterManager::class, CostCenterService::class);
        $this->app->singleton(CustomerManager::class, CustomerService::class);
        $this->app->singleton(VendorManager::class, VendorService::class);
        $this->app->singleton(CategoryManager::class, CategoryService::class);
        $this->app->singleton(UnitManager::class, UnitService::class);
        $this->app->singleton(ItemManager::class, ItemService::class);
        $this->app->singleton(WarehouseManager::class, WarehouseService::class);
        $this->app->singleton(BankManager::class, BankService::class);
        $this->app->singleton(TaxRateManager::class, TaxRateService::class);
        $this->app->singleton(PaymentTermManager::class, PaymentTermService::class);
        $this->app->singleton(AccountManager::class, AccountService::class);
        $this->app->singleton(SystemAccountManager::class, SystemAccountService::class);
        $this->app->singleton(AccountingPeriodManager::class, AccountingPeriodService::class);
        $this->app->singleton(FinancialYearManager::class, FinancialYearService::class);
        $this->app->singleton(JournalManager::class, JournalService::class);
        $this->app->singleton(JournalPoster::class, JournalPostingService::class);
        $this->app->singleton(LedgerService::class, GeneralLedgerService::class);
        $this->app->singleton(PartyLedgerService::class, PartyLedgerServiceImpl::class);
        $this->app->singleton(AgingService::class, AgingServiceImpl::class);
        $this->app->singleton(BankAccountManager::class, BankAccountService::class);
        $this->app->singleton(BankReconciliationManager::class, BankReconciliationService::class);
        $this->app->singleton(StockLedger::class, NullStockLedger::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Model::shouldBeStrict(! $this->app->isProduction());
        Date::use(CarbonImmutable::class);

        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            if (($arguments[0] ?? null) instanceof SupplierInvoice
                && in_array($ability, ['approve', 'reject'], true)) {
                return null;
            }

            return $user->hasRole('super-admin') ? true : null;
        });

        Paginator::useBootstrapFive();

    }
}
