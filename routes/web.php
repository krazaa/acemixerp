<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountingPeriodController;
use App\Http\Controllers\AgingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerLedgerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\FinancialYearController;
use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemImageController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationLogoController;
use App\Http\Controllers\PaymentTermController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SystemAccountController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorLedgerController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware('auth', 'active', 'verified')->group(function () {
    Route::get('push/config', [PushSubscriptionController::class, 'config'])->name('push.config');
    Route::post('push/subscription', [PushSubscriptionController::class, 'store'])->middleware('throttle:30,1')->name('push.store');
    Route::delete('push/subscription', [PushSubscriptionController::class, 'destroy'])->name('push.destroy');
    Route::post('push/test', [PushSubscriptionController::class, 'test'])->middleware('throttle:3,1')->name('push.test');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('notifications/{notification}', [NotificationController::class, 'update'])->whereUuid('notification')->name('notifications.update');
    Route::post('notifications/{notification}/open', [NotificationController::class, 'open'])->whereUuid('notification')->name('notifications.open');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/organization', [OrganizationController::class, 'edit'])
        ->name('organization.edit');
    Route::put('/organization', [OrganizationController::class, 'update'])
        ->name('organization.update');

    Route::get('/organization/logo', OrganizationLogoController::class)
        ->name('organization.logo');

    Route::resource('users', UserController::class);
    Route::patch('users/{user}/status', [UserController::class, 'changeStatus'])
        ->name('users.change-status');

    // Roles
    Route::resource('roles', RoleController::class)->except(['show']);

    // Permissions
    Route::resource('permissions', PermissionController::class)->except(['show']);

    // Departments
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::patch('departments/{department}/status', [DepartmentController::class, 'changeStatus'])
        ->name('departments.change-status');

    // Designations
    Route::resource('designations', DesignationController::class)->except(['show']);
    Route::patch('designations/{designation}/status', [DesignationController::class, 'changeStatus'])
        ->name('designations.change-status');

    // Cost Centers (hyphenated URI, snake_case route parameter)
    Route::resource('cost-centers', CostCenterController::class)
        ->except(['show'])
        ->parameters(['cost-centers' => 'cost_center']);
    Route::patch('cost-centers/{cost_center}/status', [CostCenterController::class, 'changeStatus'])
        ->name('cost-centers.change-status');

    // Customers
    Route::resource('customers', CustomerController::class);
    Route::patch('customers/{customer}/status', [CustomerController::class, 'changeStatus'])
        ->name('customers.change-status');

    // Vendors
    Route::resource('vendors', VendorController::class);
    Route::patch('vendors/{vendor}/status', [VendorController::class, 'changeStatus'])
        ->name('vendors.change-status');

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::patch('categories/{category}/status', [CategoryController::class, 'changeStatus'])
        ->name('categories.change-status');

    // Units
    Route::resource('units', UnitController::class)->except(['show']);
    Route::patch('units/{unit}/status', [UnitController::class, 'changeStatus'])
        ->name('units.change-status');

    // Items
    Route::resource('items', ItemController::class);
    Route::patch('items/{item}/status', [ItemController::class, 'changeStatus'])
        ->name('items.change-status');

    Route::get('items/{item}/image', ItemImageController::class)
        ->name('items.image');

    Route::resource('warehouses', WarehouseController::class);
    Route::patch('warehouses/{warehouse}/status', [WarehouseController::class, 'changeStatus'])
        ->name('warehouses.change-status');
    Route::patch('warehouses/{warehouse}/make-default', [WarehouseController::class, 'makeDefault'])
        ->name('warehouses.make-default');

    // Banks
    Route::resource('banks', BankController::class);
    Route::patch('banks/{bank}/status', [BankController::class, 'changeStatus'])
        ->name('banks.change-status');

    // Tax Rates
    Route::resource('tax-rates', TaxRateController::class)
        ->except(['show'])
        ->parameters(['tax-rates' => 'tax_rate']);
    Route::patch('tax-rates/{tax_rate}/status', [TaxRateController::class, 'changeStatus'])
        ->name('tax-rates.change-status');
    Route::patch('tax-rates/{tax_rate}/make-default', [TaxRateController::class, 'makeDefault'])
        ->name('tax-rates.make-default');

    // Payment Terms
    Route::resource('payment-terms', PaymentTermController::class)
        ->except(['show'])
        ->parameters(['payment-terms' => 'payment_term']);
    Route::patch('payment-terms/{payment_term}/status', [PaymentTermController::class, 'changeStatus'])
        ->name('payment-terms.change-status');
    Route::patch('payment-terms/{payment_term}/make-default', [PaymentTermController::class, 'makeDefault'])
        ->name('payment-terms.make-default');

    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Chart of Accounts
Route::resource('accounts', AccountController::class)->except(['show']);
Route::patch('accounts/{account}/status', [AccountController::class, 'changeStatus'])
    ->name('accounts.change-status');

// Financial Years
Route::get('financial-years', [FinancialYearController::class, 'index'])
    ->name('financial-years.index');
Route::get('financial-years/create', [FinancialYearController::class, 'create'])
    ->name('financial-years.create');
Route::post('financial-years', [FinancialYearController::class, 'store'])
    ->name('financial-years.store');
Route::patch('financial-years/{financialYear}/mark-current',
    [FinancialYearController::class, 'markCurrent'])->name('financial-years.mark-current');
Route::patch('financial-years/{financialYear}/begin-closing',
    [FinancialYearController::class, 'beginClosing'])->name('financial-years.begin-closing');
Route::patch('financial-years/{financialYear}/close',
    [FinancialYearController::class, 'close'])->name('financial-years.close');

// Accounting Periods
Route::get('accounting-periods', [AccountingPeriodController::class, 'index'])
    ->name('accounting-periods.index');
Route::post('accounting-periods/{financialYear}/generate',
    [AccountingPeriodController::class, 'generate'])->name('accounting-periods.generate');
Route::patch('accounting-periods/{accountingPeriod}/close',
    [AccountingPeriodController::class, 'close'])->name('accounting-periods.close');
Route::patch('accounting-periods/{accountingPeriod}/reopen',
    [AccountingPeriodController::class, 'reopen'])->name('accounting-periods.reopen');

// System Account mapping
Route::get('system-accounts', [SystemAccountController::class, 'edit'])
    ->name('system-accounts.edit');
Route::put('system-accounts', [SystemAccountController::class, 'update'])
    ->name('system-accounts.update');

Route::resource('journals', JournalEntryController::class)
    ->parameters(['journals' => 'journal']);

Route::prefix('journals/{journal}')->name('journals.')->group(function () {
    Route::patch('submit', [JournalEntryController::class, 'submit'])->name('submit');
    Route::patch('approve', [JournalEntryController::class, 'approve'])->name('approve');
    Route::patch('reject', [JournalEntryController::class, 'reject'])->name('reject');
    Route::patch('post', [JournalEntryController::class, 'post'])->name('post');
    Route::post('reverse', [JournalEntryController::class, 'reverse'])->name('reverse');
    Route::patch('cancel', [JournalEntryController::class, 'cancel'])->name('cancel');
});

// Reports
Route::get('gl', [GeneralLedgerController::class, 'index'])->name('gl.index');
Route::get('gl/export/csv', [GeneralLedgerController::class, 'csv'])->name('gl.csv');
Route::get('gl/print', [GeneralLedgerController::class, 'print'])->name('gl.print');
Route::get('gl/trial-balance', TrialBalanceController::class)->name('gl.trial-balance');
Route::get('gl/aging/receivables', [AgingController::class, 'receivables'])->name('gl.aging.receivables');
Route::get('gl/aging/payables', [AgingController::class, 'payables'])->name('gl.aging.payables');

// Party ledgers
Route::get('customers/{customer}/ledger', CustomerLedgerController::class)->name('customers.ledger');
Route::get('vendors/{vendor}/ledger', VendorLedgerController::class)->name('vendors.ledger');
Route::get('vendors/{vendor}/statement',
    [VendorLedgerController::class, 'statement'])
    ->name('vendors.statement');

// Bank accounts
Route::resource('bank-accounts', BankAccountController::class)
    ->parameters(['bank-accounts' => 'bankAccount']);
Route::patch('bank-accounts/{bankAccount}/make-default',
    [BankAccountController::class, 'makeDefault'])->name('bank-accounts.make-default');

// Bank reconciliation
Route::get('bank-reconciliation', [BankReconciliationController::class, 'index'])
    ->name('bank-reconciliation.index');
Route::post('bank-reconciliation', [BankReconciliationController::class, 'store'])
    ->name('bank-reconciliation.store');
Route::get('bank-reconciliation/{bankReconciliation}',
    [BankReconciliationController::class, 'show'])->name('bank-reconciliation.show');
Route::post('bank-reconciliation/{bankReconciliation}/import',
    [BankReconciliationController::class, 'import'])->name('bank-reconciliation.import');
Route::patch('bank-reconciliation/{bankReconciliation}/complete',
    [BankReconciliationController::class, 'complete'])->name('bank-reconciliation.complete');
Route::patch('bank-statement-lines/{bankStatementLine}/match',
    [BankReconciliationController::class, 'match'])->name('bank-statement-lines.match');
Route::patch('bank-statement-lines/{bankStatementLine}/unmatch',
    [BankReconciliationController::class, 'unmatch'])->name('bank-statement-lines.unmatch');

Route::resource('accounting-periods', AccountingPeriodController::class)
    ->parameters(['accounting-periods' => 'accountingPeriod'])
    ->only(['index', 'show']);

Route::prefix('accounting-periods')->name('accounting-periods.')->group(function () {
    Route::post('{financialYear}/generate', [AccountingPeriodController::class, 'generate'])
        ->name('generate');
});

Route::prefix('accounting-periods/{accountingPeriod}')->name('accounting-periods.')->group(function () {
    Route::patch('close', [AccountingPeriodController::class, 'close'])->name('close');
    Route::patch('reopen', [AccountingPeriodController::class, 'reopen'])->name('reopen');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.attempt');
});

require __DIR__.'/auth.php';

Route::get('firebase-messaging-sw.js', [PushSubscriptionController::class, 'worker'])->name('push.worker');
