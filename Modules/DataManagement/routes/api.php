<?php

use Illuminate\Support\Facades\Route;
use Modules\DataManagement\Http\Controllers\MasterKategoriController;
use Shared\Constants\AvailablePermissionConstantsHelper;
use Shared\Constants\AvailableRoleConstantsHelper;
use Shared\Constants\AvailableServiceFeatureConstantsHelper;
use Shared\Constants\AvailableServiceModuleConstantsHelper;
use Shared\Constants\MiddlewareConstantsHelper;

Route::prefix('v1/data-management')->middleware([
    MiddlewareConstantsHelper::DESAHUB_AUTH_API,
    sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_USER_ROLE, AvailableRoleConstantsHelper::SUPERADMIN, AvailableRoleConstantsHelper::ADMIN),
    sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_MODULE_PERMISSION, AvailableServiceModuleConstantsHelper::SERVICE_DATA_MANAGEMENT),
])->group(function () {
    Route::prefix('master-kategori')->middleware([
        sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_FEATURE_PERMISSION, AvailableServiceFeatureConstantsHelper::DATA_MANAGEMENT_MASTER_KATEGORI),
    ])->group(function () {
        Route::middleware([
            sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::DATA_MANAGEMENT_MASTER_KATEGORI, AvailablePermissionConstantsHelper::READ_LIST),
        ])->get('', [MasterKategoriController::class, 'index'])->name('data-management.master-kategori.index');

        Route::middleware([
            sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::DATA_MANAGEMENT_MASTER_KATEGORI, AvailablePermissionConstantsHelper::CREATE),
        ])->post('', [MasterKategoriController::class, 'store'])->name('data-management.master-kategori.store');

        Route::middleware([
            sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::DATA_MANAGEMENT_MASTER_KATEGORI, AvailablePermissionConstantsHelper::READ_DETAIL),
        ])->get('{uuid}', [MasterKategoriController::class, 'show'])->name('data-management.master-kategori.show');

        Route::middleware([
            sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::DATA_MANAGEMENT_MASTER_KATEGORI, AvailablePermissionConstantsHelper::UPDATE),
        ])->put('{uuid}', [MasterKategoriController::class, 'update'])->name('data-management.master-kategori.update');

        Route::middleware([
            sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::DATA_MANAGEMENT_MASTER_KATEGORI, AvailablePermissionConstantsHelper::DELETE),
        ])->delete('{uuid}', [MasterKategoriController::class, 'destroy'])->name('data-management.master-kategori.destroy');
    });
});
