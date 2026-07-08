<?php

namespace Modules\IAMService\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAMService\Models\Permission;
use Modules\IAMService\Models\Role;
use Modules\IAMService\Models\RolePermissionServiceFeature;
use Modules\IAMService\Models\ServiceFeature;
use Shared\Constants\AvailablePermissionConstantsHelper;
use Shared\Constants\AvailableRoleConstantsHelper;
use Shared\Constants\AvailableServiceFeatureConstantsHelper;

class RolePermissionServiceFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSuperadminFullAccess();
        $this->seedAdminAccess();
    }

    private function seedSuperadminFullAccess(): void
    {
        $role = Role::query()->where('role', AvailableRoleConstantsHelper::SUPERADMIN)->firstOrFail();
        $serviceFeatures = ServiceFeature::query()->active()->notDeleted()->get();
        $permissions = Permission::query()->active()->notDeleted()->get();

        foreach ($serviceFeatures as $serviceFeature) {
            foreach ($permissions as $permission) {
                $this->upsertAccessControl($role->uuid, $serviceFeature->uuid, $permission->uuid);
            }
        }
    }

    private function seedAdminAccess(): void
    {
        $role = Role::query()->where('role', AvailableRoleConstantsHelper::ADMIN)->firstOrFail();

        $adminFeatures = [
            AvailableServiceFeatureConstantsHelper::IAM_USER_MANAGEMENT,
            AvailableServiceFeatureConstantsHelper::IAM_RBAC_ROLE,
            AvailableServiceFeatureConstantsHelper::IAM_RBAC_SERVICE_FEATURE,
            AvailableServiceFeatureConstantsHelper::IAM_RBAC_PERMISSION,
            AvailableServiceFeatureConstantsHelper::IAM_RBAC_ACCESS_CONTROL,
        ];

        $adminPermissions = [
            AvailablePermissionConstantsHelper::READ_LIST,
            AvailablePermissionConstantsHelper::READ_DETAIL,
            AvailablePermissionConstantsHelper::CREATE,
            AvailablePermissionConstantsHelper::UPDATE,
        ];

        $serviceFeatures = ServiceFeature::query()
            ->active()
            ->notDeleted()
            ->whereIn('service_feature_name', $adminFeatures)
            ->get();

        $permissions = Permission::query()
            ->active()
            ->notDeleted()
            ->whereIn('name', $adminPermissions)
            ->get();

        foreach ($serviceFeatures as $serviceFeature) {
            foreach ($permissions as $permission) {
                $this->upsertAccessControl($role->uuid, $serviceFeature->uuid, $permission->uuid);
            }
        }
    }

    private function upsertAccessControl(string $roleUuid, string $serviceFeatureUuid, string $permissionUuid): void
    {
        $existing = RolePermissionServiceFeature::query()
            ->where('uuid_role', $roleUuid)
            ->where('uuid_service_feature', $serviceFeatureUuid)
            ->where('uuid_permission', $permissionUuid)
            ->first();

        RolePermissionServiceFeature::query()->updateOrCreate(
            [
                'uuid_role' => $roleUuid,
                'uuid_service_feature' => $serviceFeatureUuid,
                'uuid_permission' => $permissionUuid,
            ],
            [
                'uuid' => $existing?->uuid ?? generateUuid(),
                'is_active' => true,
                'is_deleted' => false,
                'created_by' => 'seeder',
                'updated_by' => 'seeder',
            ]
        );
    }
}
