<?php

use Illuminate\Support\Facades\Route;
use Modules\MarketplaceUmkmService\Http\Controllers\MarketplaceUmkmServiceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('marketplaceumkmservices', MarketplaceUmkmServiceController::class)->names('marketplaceumkmservice');
});
