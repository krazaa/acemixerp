<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\HrController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('hrs', HrController::class)->names('hr');
});
