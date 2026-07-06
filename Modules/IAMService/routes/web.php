<?php

use Illuminate\Support\Facades\Route;
use Modules\IAMService\Http\Controllers\IAMServiceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('iamservices', IAMServiceController::class)->names('iamservice');
});
