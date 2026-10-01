<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryController;
use Modules\Inventory\Http\Controllers\OriginController;
use Modules\Inventory\Http\Controllers\StockAdjustmentController;
use Modules\Inventory\Http\Controllers\StockController;
use Modules\Inventory\Http\Controllers\StockCountController;
use Modules\Inventory\Http\Controllers\StockMovementController;
use Modules\Inventory\Http\Controllers\StockTransferController;

Route::middleware(['auth', 'verified'])
    ->prefix('inventory')
    ->name('inventory.')
    ->group(function () {
        Route::resource('brands', \Modules\Inventory\Http\Controllers\BrandController::class)->except('show')->middleware('active');
        Route::get('/', [InventoryController::class, 'dashboard'])->name('dashboard');
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/{item}', [StockController::class, 'show'])->name('stock.show');
        Route::get('movements', [StockMovementController::class, 'index'])->name('movements.index');
        Route::get('warehouses/{warehouse}', [InventoryController::class, 'warehouse'])->name('warehouse');
        Route::get('low-stock', [InventoryController::class, 'lowStock'])->name('low-stock');
        Route::get('summary', [InventoryController::class, 'summary'])->name('summary');
        Route::get('summary/print', [InventoryController::class, 'summaryPrint'])->name('summary.print');
        Route::get('summary/export/csv', [InventoryController::class, 'summaryCsv'])->name('summary.csv');
        Route::get('valuation', [InventoryController::class, 'valuation'])->name('valuation');
        Route::get('valuation/print', [InventoryController::class, 'valuationPrint'])->name('valuation.print');
        Route::get('valuation/export/csv', [InventoryController::class, 'valuationCsv'])->name('valuation.csv');
        Route::get('on-hand', [InventoryController::class, 'onHand'])->name('on-hand');

        Route::resource('transfers', StockTransferController::class)->parameters(['transfers' => 'transfer']);
        Route::prefix('transfers/{transfer}')->name('transfers.')->group(function () {
            Route::patch('submit', [StockTransferController::class, 'submit'])->name('submit');
            Route::patch('approve', [StockTransferController::class, 'approve'])->name('approve');
            Route::patch('dispatch', [StockTransferController::class, 'dispatch'])->name('dispatch');
            Route::patch('receive', [StockTransferController::class, 'receive'])->name('receive');
            Route::patch('cancel', [StockTransferController::class, 'cancel'])->name('cancel');
        });

        Route::resource('adjustments', StockAdjustmentController::class)->parameters(['adjustments' => 'adjustment']);
        Route::prefix('adjustments/{adjustment}')->name('adjustments.')->group(function () {
            Route::patch('submit', [StockAdjustmentController::class, 'submit'])->name('submit');
            Route::patch('approve', [StockAdjustmentController::class, 'approve'])->name('approve');
            Route::patch('post', [StockAdjustmentController::class, 'post'])->name('post');
            Route::patch('cancel', [StockAdjustmentController::class, 'cancel'])->name('cancel');
        });

        Route::resource('counts', StockCountController::class)->parameters(['counts' => 'count']);
        Route::prefix('counts/{count}')->name('counts.')->group(function () {
            Route::patch('start', [StockCountController::class, 'start'])->name('start');
            Route::patch('snapshot', [StockCountController::class, 'snapshot'])->name('snapshot');
            Route::post('record', [StockCountController::class, 'record'])->name('record');
            Route::patch('submit', [StockCountController::class, 'submit'])->name('submit');
            Route::patch('approve', [StockCountController::class, 'approve'])->name('approve');
            Route::patch('post', [StockCountController::class, 'post'])->name('post');
            Route::patch('cancel', [StockCountController::class, 'cancel'])->name('cancel');
        });

            Route::resource('origins', OriginController::class);
    });
