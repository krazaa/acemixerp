<?php

use Illuminate\Support\Facades\Route;
use Modules\FixedAssets\Http\Controllers\FixedAssetsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('fixedassets', FixedAssetsController::class)->names('fixedassets');
});
