<?php

use Illuminate\Support\Facades\Route;
use Modules\DropdownService\Http\Controllers\DropdownServiceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('dropdownservices', DropdownServiceController::class)->names('dropdownservice');
});
