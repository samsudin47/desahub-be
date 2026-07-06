<?php

use Illuminate\Support\Facades\Route;
use Modules\BeritaDesa\Http\Controllers\BeritaDesaController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('beritadesas', BeritaDesaController::class)->names('beritadesa');
});
