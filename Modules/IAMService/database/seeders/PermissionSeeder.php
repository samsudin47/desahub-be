<?php

namespace Modules\IAMService\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAMService\Models\Permission;
use Shared\Constants\AvailablePermissionConstantsHelper;

class PermissionSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private array $permissions = [
        AvailablePermissionConstantsHelper::READ_LIST => 'Read List',
        AvailablePermissionConstantsHelper::READ_DETAIL => 'Read Detail',
        AvailablePermissionConstantsHelper::CREATE => 'Create',
        AvailablePermissionConstantsHelper::UPDATE => 'Update',
        AvailablePermissionConstantsHelper::DELETE => 'Delete',
    ];

    public function run(): void
    {
        foreach ($this->permissions as $name => $label) {
            Permission::query()->updateOrCreate(
                ['name' => $name],
                [
                    'uuid' => Permission::query()->where('name', $name)->value('uuid') ?? generateUuid(),
                    'label' => $label,
                    'description' => $label,
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
