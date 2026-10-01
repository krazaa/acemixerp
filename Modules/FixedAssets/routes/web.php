<?php

use Illuminate\Support\Facades\Route;
use Modules\FixedAssets\Http\Controllers\AssetController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('fixed-assets', [AssetController::class, 'index'])->name('fixed-assets.index');
    Route::get('fixed-assets/create', [AssetController::class, 'create'])->name('fixed-assets.create');
    Route::post('fixed-assets', [AssetController::class, 'store'])->name('fixed-assets.store');
    Route::get('fixed-assets/{asset}', [AssetController::class, 'show'])->name('fixed-assets.show');
    Route::post('fixed-assets/{asset}/lifecycle', [AssetController::class, 'lifecycle'])->name('fixed-assets.lifecycle');
});
