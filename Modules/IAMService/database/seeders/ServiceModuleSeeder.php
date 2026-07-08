<?php

namespace Modules\IAMService\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAMService\Models\ServiceModule;
use Shared\Constants\AvailableServiceModuleConstantsHelper;

class ServiceModuleSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, description: string}>
     */
    private array $modules = [
        AvailableServiceModuleConstantsHelper::SERVICE_IAM => [
            'name' => 'IAM Service',
            'description' => 'Identity and access management service module',
        ],
        AvailableServiceModuleConstantsHelper::SERVICE_IAM_RBAC => [
            'name' => 'IAM RBAC',
            'description' => 'Role-based access control management module',
        ],
    ];

    public function run(): void
    {
        foreach ($this->modules as $code => $module) {
            ServiceModule::query()->updateOrCreate(
                ['code' => $code],
                [
                    'uuid' => ServiceModule::query()->where('code', $code)->value('uuid') ?? generateUuid(),
                    'name' => $module['name'],
                    'description' => $module['description'],
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
