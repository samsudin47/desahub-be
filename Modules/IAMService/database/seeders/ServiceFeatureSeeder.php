<?php

namespace Modules\IAMService\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAMService\Models\ServiceFeature;
use Modules\IAMService\Models\ServiceModule;
use Shared\Constants\AvailableServiceFeatureConstantsHelper;
use Shared\Constants\AvailableServiceModuleConstantsHelper;

class ServiceFeatureSeeder extends Seeder
{
    /**
     * @var array<string, array{module: string, name: string, description: string}>
     */
    private array $features = [
        AvailableServiceFeatureConstantsHelper::IAM_USER_MANAGEMENT => [
            'module' => AvailableServiceModuleConstantsHelper::SERVICE_IAM,
            'name' => 'User Management',
            'description' => 'Manage IAM users',
        ],
        AvailableServiceFeatureConstantsHelper::IAM_RBAC_ROLE => [
            'module' => AvailableServiceModuleConstantsHelper::SERVICE_IAM_RBAC,
            'name' => 'RBAC Role',
            'description' => 'Manage RBAC roles',
        ],
        AvailableServiceFeatureConstantsHelper::IAM_RBAC_SERVICE_FEATURE => [
            'module' => AvailableServiceModuleConstantsHelper::SERVICE_IAM_RBAC,
            'name' => 'RBAC Service Feature',
            'description' => 'Manage RBAC service features',
        ],
        AvailableServiceFeatureConstantsHelper::IAM_RBAC_PERMISSION => [
            'module' => AvailableServiceModuleConstantsHelper::SERVICE_IAM_RBAC,
            'name' => 'RBAC Permission',
            'description' => 'Manage RBAC permissions',
        ],
        AvailableServiceFeatureConstantsHelper::IAM_RBAC_ACCESS_CONTROL => [
            'module' => AvailableServiceModuleConstantsHelper::SERVICE_IAM_RBAC,
            'name' => 'RBAC Access Control',
            'description' => 'Manage RBAC access control mappings',
        ],
        AvailableServiceFeatureConstantsHelper::DATA_MANAGEMENT_MASTER_KATEGORI => [
            'module' => AvailableServiceModuleConstantsHelper::SERVICE_DATA_MANAGEMENT,
            'name' => 'Master Kategori',
            'description' => 'Manage master kategori data',
        ],
    ];

    public function run(): void
    {
        foreach ($this->features as $featureCode => $feature) {
            $serviceModule = ServiceModule::query()
                ->where('code', $feature['module'])
                ->firstOrFail();

            ServiceFeature::query()->updateOrCreate(
                [
                    'service_module' => $feature['module'],
                    'service_feature_name' => $featureCode,
                ],
                [
                    'uuid' => ServiceFeature::query()
                        ->where('service_module', $feature['module'])
                        ->where('service_feature_name', $featureCode)
                        ->value('uuid') ?? generateUuid(),
                    'uuid_service_module' => $serviceModule->uuid,
                    'name' => $feature['name'],
                    'description' => $feature['description'],
                    'is_system' => true,
                    'is_active' => true,
                    'is_deleted' => false,
                    'created_by' => 'seeder',
                    'updated_by' => 'seeder',
                ]
            );
        }
    }
}
