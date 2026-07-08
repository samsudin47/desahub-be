<?php

namespace Modules\IAMService\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAMService\Models\Role;
use Shared\Constants\AvailableRoleConstantsHelper;

class RoleSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, description: string}>
     */
    private array $roles = [
        AvailableRoleConstantsHelper::SUPERADMIN => [
            'name' => 'Superadmin',
            'description' => AvailableRoleConstantsHelper::SUPERADMIN_DESC,
        ],
        AvailableRoleConstantsHelper::ADMIN => [
            'name' => 'Admin',
            'description' => AvailableRoleConstantsHelper::ADMIN_DESC,
        ],
        AvailableRoleConstantsHelper::WARGA => [
            'name' => 'Warga',
            'description' => AvailableRoleConstantsHelper::WARGA_DESC,
        ],
        AvailableRoleConstantsHelper::USER => [
            'name' => 'User',
            'description' => AvailableRoleConstantsHelper::USER_DESC,
        ],
    ];

    public function run(): void
    {
        foreach ($this->roles as $roleCode => $role) {
            Role::query()->updateOrCreate(
                ['role' => $roleCode],
                [
                    'uuid' => Role::query()->where('role', $roleCode)->value('uuid') ?? generateUuid(),
                    'name' => $role['name'],
                    'description' => $role['description'],
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
