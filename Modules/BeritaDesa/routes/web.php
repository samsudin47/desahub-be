<?php

use Illuminate\Support\Facades\Route;
use Modules\BeritaDesa\Http\Controllers\BeritaDesaController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('beritadesas', BeritaDesaController::class)->names('beritadesa');
});
