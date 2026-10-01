<?php

use Illuminate\Support\Facades\Route;
use Modules\Manufacturing\Http\Controllers\BomController;
use Modules\Manufacturing\Http\Controllers\ProductionOrderController;

Route::middleware(['auth', 'verified'])
    ->prefix('manufacturing')
    ->name('manufacturing.')
    ->group(function () {
        // BOMs
        Route::get('boms/ingredients/{ingredient}/batches', [BomController::class, 'ingredientBatches'])
            ->name('boms.ingredient-batches');
        Route::resource('boms', BomController::class)->parameters(['boms' => 'bom']);
        Route::patch('boms/{bom}/activate', [BomController::class, 'activate'])->name('boms.activate');

        // Production Orders
        Route::resource('production-orders', ProductionOrderController::class)
            ->parameters(['production-orders' => 'productionOrder']);
        Route::get('production-orders/{productionOrder}/print', [ProductionOrderController::class, 'print'])
            ->name('production-orders.print');

        Route::prefix('production-orders/{productionOrder}')->name('production-orders.')->group(function () {
            Route::patch('plan', [ProductionOrderController::class, 'plan'])->name('plan');
            Route::patch('release', [ProductionOrderController::class, 'release'])->name('release');
            Route::patch('start', [ProductionOrderController::class, 'start'])->name('start');
            Route::patch('complete', [ProductionOrderController::class, 'complete'])->name('complete');
            Route::patch('close', [ProductionOrderController::class, 'close'])->name('close');
            Route::patch('cancel', [ProductionOrderController::class, 'cancel'])->name('cancel');
        });
    });
