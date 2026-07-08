<?php

use Illuminate\Support\Facades\Route;
use Modules\MarketplaceService\Http\Controllers\MarketplaceServiceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('marketplaceservices', MarketplaceServiceController::class)->names('marketplaceservice');
});
