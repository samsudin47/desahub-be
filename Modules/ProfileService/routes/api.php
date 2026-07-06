<?php

use Illuminate\Support\Facades\Route;
use Modules\ProfileService\Http\Controllers\ProfileServiceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('profileservices', ProfileServiceController::class)->names('profileservice');
});
