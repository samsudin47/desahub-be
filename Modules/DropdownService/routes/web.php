<?php

use Illuminate\Support\Facades\Route;
use Modules\DropdownService\Http\Controllers\DropdownServiceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('dropdownservices', DropdownServiceController::class)->names('dropdownservice');
});
