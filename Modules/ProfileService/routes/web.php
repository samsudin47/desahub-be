<?php

use Illuminate\Support\Facades\Route;
use Modules\ProfileService\Http\Controllers\ProfileServiceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('profileservices', ProfileServiceController::class)->names('profileservice');
});
