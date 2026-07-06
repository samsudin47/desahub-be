<?php

use Illuminate\Support\Facades\Auth;
use Modules\IAMService\Models\Permission;
use Modules\IAMService\Models\RolePermissionServiceFeature;
use Modules\IAMService\Models\ServiceFeature;
use Modules\IAMService\Models\User;
use Shared\Constants\AvailableRoleConstantsHelper;

function userAuthenticatedPermission(): array
{
    $userAuthenticated = Auth::guard('api')->user();

    $userRoles = $userAuthenticated->roles()->get();
    $uuidUserRoles = $userRoles->pluck('uuid')->toArray();

    $rolePermissionServiceFeatures = RolePermissionServiceFeature::active()->whereIn('uuid_role', $uuidUserRoles)->get();
    $uuidServiceFeatures = $rolePermissionServiceFeatures->pluck('uuid_service_feature')->unique()->values()->toArray();
    $uuidPermissions = $rolePermissionServiceFeatures->pluck('uuid_permission')->unique()->values()->toArray();

    $chunks = array_chunk($uuidServiceFeatures, 50);
    $serviceFeatures = collect();
    foreach ($chunks as $chunk) {
        $features = ServiceFeature::active()->whereIn('uuid', $chunk)->get();
        $serviceFeatures = $serviceFeatures->merge($features);
    }
    $mappedUserServiceFeatures = $serviceFeatures->mapWithKeys(fn($serviceFeature) => [$serviceFeature->uuid => $serviceFeature->service_module.'|'.$serviceFeature->service_feature_name]);

    $permissions = Permission::active()->whereIn('uuid', $uuidPermissions)->get();
    $mappedUserPermissions = $permissions->mapWithKeys(fn($permission) => [$permission->uuid => $permission->name]);

    $userGrantedPermission = [];
    $userGrantedPermission["roles"] = $userRoles->pluck('role')->toArray();
    $availableServices = availableServices();
    $availableRole = $userRoles->mapWithKeys(function ($role) {
        return [$role->uuid => $role->role];
    })->toArray();
    $userGrantedPermission["permissions"] = [];
    foreach ($userRoles as $role) {
        $userGrantedPermission["permissions"][$role->role] = $availableServices;
    }

    foreach ($rolePermissionServiceFeatures as $rolePermissionServiceFeature) {
        $services = $mappedUserServiceFeatures[$rolePermissionServiceFeature->uuid_service_feature];
        $moduleName = explode('|', $services)[0];
        $featureName = explode('|', $services)[1];
        $userGrantedPermission["permissions"][$availableRole[$rolePermissionServiceFeature->uuid_role]][$moduleName][$featureName][] = $mappedUserPermissions[$rolePermissionServiceFeature->uuid_permission];
    }

    return $userGrantedPermission;
}

function availableServices(): array
{
    return ServiceFeature::active()->get()->groupBy('service_module')->map(fn ($serviceFeature) => $serviceFeature->mapWithKeys(fn ($feature) => [
        $feature->service_feature_name => []
    ]))->toArray();
}

function getUserAuthenticatedRoles(): array
{
    return userAuthenticatedPermission()['roles'];
}

function getUserAuthenticatedPermission(): array
{
    $rolePermissionsServiceFeatures = userAuthenticatedPermission()['permissions'];

    $grantedPermissions = [];
    foreach ($rolePermissionsServiceFeatures as $roles => $services) {
        $grantedPermissions['roles'][] = $roles;
        foreach ($services as $module => $features) {
            $grantedPermissions['modules'][] = $module;
            foreach ($features as $feature => $accessControl) {
                $grantedPermissions['features'][] = $feature;
                $existing = $grantedPermissions['access'][$feature] ?? [];
                $grantedPermissions['access'][$feature] = array_values(array_unique(array_merge($existing, (array) $accessControl)));
            }
        }
    }

    $grantedPermissions['modules'] = array_unique($grantedPermissions['modules']);
    $grantedPermissions['features'] = array_unique($grantedPermissions['features']);

    return $grantedPermissions;
}

function getUserAuthenticatedModulePermission(): array
{
    return getUserAuthenticatedPermission()['modules'];
}

function getUserAuthenticatedFeaturePermission(): array
{
    return getUserAuthenticatedPermission()['features'];
}

function getUserAuthenticatedAccessPermission(): array
{
    return getUserAuthenticatedPermission()['access'];
}

function getUserId()
{
    try {
        return Auth::guard('api')->user()->uuid;
    } catch (Exception $e) {
        return "-";
    }
}

function getUserName()
{
    try {
        return Auth::guard('api')->user()->username;
    } catch (Exception $e) {
        return "-";
    }
}

function getUserRoles($role)
{
    $userAuthenticated = Auth::guard('api')->user();

    $isUserHasRoleSpecific = $userAuthenticated->roles->contains('role', $role);
    if (!$isUserHasRoleSpecific) {
        return false;
    }

    return true;
}

function getUserDataByUserId()
{
    return User::active()->with('karyawan')->where('uuid', getUserId())->first();
}

function getUserEmployeeDataByUserId()
{
    return getUserDataByUserId()?->karyawan;
}

function getUserNoPokokLoggedIn()
{
    return getUserDataByUserId()?->karyawan?->no_pokok;
}

function getUserOrganizationDataByUserId()
{
    return getUserDataByUserId()?->karyawan?->organization;
}

function getEmployeeUuidByUserLoggedIn()
{
    return getUserDataByUserId()?->karyawan?->uuid;
}

function isHasRoleSuperadmin(): bool
{
    return getUserRoles(AvailableRoleConstantsHelper::SUPERADMIN);
}

function isHasRoleAdminHC(): bool
{
    return getUserRoles(AvailableRoleConstantsHelper::ADMIN);
}

function isHasRoleKetuaKomiteTalent(): bool
{
    return getUserRoles(AvailableRoleConstantsHelper::WARGA);
}

function isHasRoleKomiteTalent(): bool
{
    return getUserRoles(AvailableRoleConstantsHelper::USER);
}
