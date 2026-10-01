<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Http\Controllers\ReportsController;

Route::middleware(['auth', 'active', 'verified'])->group(function () {
    Route::get('reports/accounts-payable', [ReportsController::class, 'payables'])->name('reports.accounts-payable');
    Route::get('reports/accounts-receivable', [ReportsController::class, 'receivables'])->name('reports.accounts-receivable');
    Route::get('reports/trial-balance', [ReportsController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('reports/balance-sheet', [ReportsController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
});
