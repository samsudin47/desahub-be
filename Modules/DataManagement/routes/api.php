<?php

use Illuminate\Support\Facades\Route;
use Modules\DataManagement\Http\Controllers\DataManagementController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('datamanagements', DataManagementController::class)->names('datamanagement');
});
