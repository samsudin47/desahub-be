<?php

use Illuminate\Support\Facades\Route;
use Modules\DropdownService\Http\Controllers\DropdownCategoriesController;
use Shared\Constants\AvailablePermissionConstantsHelper;
use Shared\Constants\AvailableRoleConstantsHelper;
use Shared\Constants\AvailableServiceFeatureConstantsHelper;
use Shared\Constants\AvailableServiceModuleConstantsHelper;
use Shared\Constants\MiddlewareConstantsHelper;

Route::prefix('v1/dropdown-service')->middleware([
    MiddlewareConstantsHelper::DESAHUB_AUTH_API,
    sprintf(
        '%s:%s,%s,%s,%s',
        MiddlewareConstantsHelper::DESAHUB_USER_ROLE,
        AvailableRoleConstantsHelper::SUPERADMIN,
        AvailableRoleConstantsHelper::ADMIN,
        AvailableRoleConstantsHelper::USER,
        AvailableRoleConstantsHelper::WARGA,
    ),
    sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_MODULE_PERMISSION, AvailableServiceModuleConstantsHelper::SERVICE_DROPDOWN),
])->group(function () {
    Route::prefix('kategori')->middleware([
        sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_FEATURE_PERMISSION, AvailableServiceFeatureConstantsHelper::DROPDOWN_CATEGORIES),
    ])->group(function () {
        Route::middleware([
            sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::DROPDOWN_CATEGORIES, AvailablePermissionConstantsHelper::READ_LIST),
        ])->get('', [DropdownCategoriesController::class, 'index'])->name('dropdown-service.kategori.index');
    });
});
