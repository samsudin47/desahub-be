<?php

use Illuminate\Support\Facades\Route;
use Modules\MarketplaceUmkmService\Http\Controllers\ProductCategoriesController;
use Shared\Constants\AvailableRoleConstantsHelper;
use Shared\Constants\MiddlewareConstantsHelper;

Route::prefix('v1/marketplace-umkm-service')->middleware([
    MiddlewareConstantsHelper::DESAHUB_AUTH_API,
    sprintf(
        '%s:%s,%s',
        MiddlewareConstantsHelper::DESAHUB_USER_ROLE,
        AvailableRoleConstantsHelper::USER,
        AvailableRoleConstantsHelper::WARGA,
    ),
])->group(function () {
    Route::get('product-categories/{uuid}', [ProductCategoriesController::class, 'show'])
        ->name('marketplace-umkm-service.product-categories.show');
});
