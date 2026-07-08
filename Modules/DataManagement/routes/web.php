<?php

use Illuminate\Support\Facades\Route;
use Modules\DataManagement\Http\Controllers\DataManagementController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('datamanagements', DataManagementController::class)->names('datamanagement');
});
