<?php

use Illuminate\Support\Facades\Route;
use Modules\MarketplaceService\Http\Controllers\MarketplaceServiceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('marketplaceservices', MarketplaceServiceController::class)->names('marketplaceservice');
});
