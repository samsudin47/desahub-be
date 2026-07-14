<?php

use Illuminate\Support\Facades\Route;
use Modules\MarketplaceService\Http\Controllers\ProductController;
use Shared\Constants\AvailablePermissionConstantsHelper;
use Shared\Constants\AvailableRoleConstantsHelper;
use Shared\Constants\AvailableServiceFeatureConstantsHelper;
use Shared\Constants\AvailableServiceModuleConstantsHelper;
use Shared\Constants\MiddlewareConstantsHelper;

Route::prefix('v1/marketplace-service')->middleware([
    MiddlewareConstantsHelper::DESAHUB_AUTH_API,
    sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_USER_ROLE, AvailableRoleConstantsHelper::SUPERADMIN, AvailableRoleConstantsHelper::ADMIN),
    sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_MODULE_PERMISSION, AvailableServiceModuleConstantsHelper::SERVICE_MARKETPLACE),
])->group(function () {
    Route::prefix('product')->middleware([
        sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_FEATURE_PERMISSION, AvailableServiceFeatureConstantsHelper::MARKETPLACE_PRODUCT),
    ])->group(function () {
        Route::middleware([
            sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::MARKETPLACE_PRODUCT, AvailablePermissionConstantsHelper::READ_LIST),
        ])->get('', [ProductController::class, 'index'])->name('marketplace-service.product.index');

        Route::middleware([
            sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::MARKETPLACE_PRODUCT, AvailablePermissionConstantsHelper::CREATE),
        ])->post('', [ProductController::class, 'store'])->name('marketplace-service.product.store');

        Route::middleware([
            sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::MARKETPLACE_PRODUCT, AvailablePermissionConstantsHelper::READ_DETAIL),
        ])->get('{uuid}', [ProductController::class, 'show'])->name('marketplace-service.product.show');

        Route::middleware([
            sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::MARKETPLACE_PRODUCT, AvailablePermissionConstantsHelper::UPDATE),
        ])->post('{uuid}', [ProductController::class, 'update'])->name('marketplace-service.product.update');

        Route::middleware([
            sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::MARKETPLACE_PRODUCT, AvailablePermissionConstantsHelper::DELETE),
        ])->delete('{uuid}', [ProductController::class, 'destroy'])->name('marketplace-service.product.destroy');
    });
});
