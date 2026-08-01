<?php

use Illuminate\Support\Facades\Route;
use Modules\IAMService\Http\Controllers\AuthenticationController;
use Modules\IAMService\Http\Controllers\HealthCheckController;
use Modules\IAMService\Http\Controllers\RoleBaseAccessControlController;
use Modules\IAMService\Http\Controllers\RoleBaseAccessControlPermissionController;
use Modules\IAMService\Http\Controllers\RoleBaseAccessControlRolesController;
use Modules\IAMService\Http\Controllers\RoleBaseAccessControlServiceFeatureController;
use Modules\IAMService\Http\Controllers\UserController;
use Shared\Constants\AvailablePermissionConstantsHelper;
use Shared\Constants\AvailableRoleConstantsHelper;
use Shared\Constants\AvailableServiceFeatureConstantsHelper;
use Shared\Constants\AvailableServiceModuleConstantsHelper;
use Shared\Constants\MiddlewareConstantsHelper;

$registerAuthenticationRoutes = function (string $namePrefix): void {
    Route::prefix('auth')->group(function () use ($namePrefix) {
        Route::post('register', [AuthenticationController::class, 'register'])->name("{$namePrefix}.register");
        Route::post('login', [AuthenticationController::class, 'login'])->name("{$namePrefix}.login");
        Route::post('forgot-password', [AuthenticationController::class, 'forgotPassword'])->name("{$namePrefix}.forgot-password");
        Route::post('reset-password', [AuthenticationController::class, 'resetPassword'])->name("{$namePrefix}.reset-password");

        Route::middleware(MiddlewareConstantsHelper::DESAHUB_AUTH_API)->group(function () use ($namePrefix) {
            Route::get('token-validation', [AuthenticationController::class, 'tokenValidation'])->name("{$namePrefix}.token-validation");
            Route::post('logout', [AuthenticationController::class, 'logout'])->name("{$namePrefix}.logout");
        });
    });
};

Route::prefix('v1/iam-services')->group(function () use ($registerAuthenticationRoutes) {
    Route::get('health', [HealthCheckController::class, 'index'])->name('iam-services.health');
    Route::get('roles', [UserController::class, 'getRoles'])->name('iam-services.roles');

    $registerAuthenticationRoutes('iam-services');

    Route::middleware([
        MiddlewareConstantsHelper::DESAHUB_AUTH_API,
        sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_USER_ROLE, AvailableRoleConstantsHelper::SUPERADMIN, AvailableRoleConstantsHelper::ADMIN),
        sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_MODULE_PERMISSION, AvailableServiceModuleConstantsHelper::SERVICE_IAM),
    ])->group(function () {
        Route::prefix('users')->middleware([
            sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_FEATURE_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_USER_MANAGEMENT),
        ])->group(function () {
            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_USER_MANAGEMENT, AvailablePermissionConstantsHelper::READ_LIST),
            ])->get('', [UserController::class, 'getUsers'])->name('iam-services.users');

            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_USER_MANAGEMENT, AvailablePermissionConstantsHelper::READ_LIST),
            ])->get('view-log/{uuidGetUser}', [UserController::class, 'getUserViewLog'])->name('iam-services.users.view-log');

            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_USER_MANAGEMENT, AvailablePermissionConstantsHelper::CREATE),
            ])->post('', [UserController::class, 'storeUser'])->name('iam-services.users.store');

            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_USER_MANAGEMENT, AvailablePermissionConstantsHelper::UPDATE),
            ])->put('update-status', [UserController::class, 'updateUserStatus'])->name('iam-services.users.update-status');

            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_USER_MANAGEMENT, AvailablePermissionConstantsHelper::UPDATE),
            ])->put('update-roles', [UserController::class, 'updateUserRoles'])->name('iam-services.users.update-roles');
        });
    });

    Route::middleware([
        MiddlewareConstantsHelper::DESAHUB_AUTH_API,
        sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_USER_ROLE, AvailableRoleConstantsHelper::SUPERADMIN, AvailableRoleConstantsHelper::ADMIN),
        sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_MODULE_PERMISSION, AvailableServiceModuleConstantsHelper::SERVICE_IAM_RBAC),
    ])->prefix('role-base-access-control')->group(function () {
        Route::middleware([
            sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_FEATURE_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_ROLE),
        ])->prefix('roles')->group(function () {
            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_ROLE, AvailablePermissionConstantsHelper::READ_LIST),
            ])->get('', [RoleBaseAccessControlRolesController::class, 'index'])->name('iam-services.role-base-access-control.roles.index');
        });

        Route::middleware([
            sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_FEATURE_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_SERVICE_FEATURE),
        ])->prefix('service-features')->group(function () {
            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_SERVICE_FEATURE, AvailablePermissionConstantsHelper::READ_LIST),
            ])->get('', [RoleBaseAccessControlServiceFeatureController::class, 'index'])->name('iam-services.role-base-access-control.service-features.index');

            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_SERVICE_FEATURE, AvailablePermissionConstantsHelper::READ_LIST),
            ])->get('services', [RoleBaseAccessControlServiceFeatureController::class, 'getServiceAvailable'])->name('iam-services.role-base-access-control.service-features.get-services');

            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_SERVICE_FEATURE, AvailablePermissionConstantsHelper::READ_LIST),
            ])->get('features/{service}', [RoleBaseAccessControlServiceFeatureController::class, 'getFeatureAvailableByService'])->name('iam-services.role-base-access-control.service-features.get-feature-available-by-service');
        });

        Route::middleware([
            sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_FEATURE_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_PERMISSION),
        ])->prefix('permission')->group(function () {
            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_PERMISSION, AvailablePermissionConstantsHelper::READ_LIST),
            ])->get('', [RoleBaseAccessControlPermissionController::class, 'index'])->name('iam-services.role-base-access-control.permission.index');
        });

        Route::middleware([
            sprintf('%s:%s', MiddlewareConstantsHelper::DESAHUB_FEATURE_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_ACCESS_CONTROL),
        ])->prefix('access-control')->group(function () {
            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_ACCESS_CONTROL, AvailablePermissionConstantsHelper::READ_LIST),
            ])->get('', [RoleBaseAccessControlController::class, 'index'])->name('iam-services.role-base-access-control.access-control.index');

            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_ACCESS_CONTROL, AvailablePermissionConstantsHelper::CREATE),
            ])->post('', [RoleBaseAccessControlController::class, 'store'])->name('iam-services.role-base-access-control.access-control.store');

            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_ACCESS_CONTROL, AvailablePermissionConstantsHelper::DELETE),
            ])->delete('', [RoleBaseAccessControlController::class, 'destroy'])->name('iam-services.role-base-access-control.access-control.destroy');

            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_ACCESS_CONTROL, AvailablePermissionConstantsHelper::READ_LIST),
            ])->get('get-based-on-menu', [RoleBaseAccessControlController::class, 'getBasedOnMenu'])->name('iam-services.role-base-access-control.access-control.get-based-on-menu');

            Route::middleware([
                sprintf('%s:%s,%s', MiddlewareConstantsHelper::DESAHUB_ACCESS_PERMISSION, AvailableServiceFeatureConstantsHelper::IAM_RBAC_ACCESS_CONTROL, AvailablePermissionConstantsHelper::UPDATE),
            ])->post('submit-based-on-menu', [RoleBaseAccessControlController::class, 'storeBasedOnMenu'])->name('iam-services.role-base-access-control.access-control.store-based-on-menu');
        });
    });
});

Route::prefix('v1/iam')->group(function () use ($registerAuthenticationRoutes) {
    $registerAuthenticationRoutes('iam');
});
