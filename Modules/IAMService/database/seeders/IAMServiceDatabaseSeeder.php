<?php

namespace Modules\IAMService\Database\Seeders;

use Illuminate\Database\Seeder;

class IAMServiceDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ServiceModuleSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            ServiceFeatureSeeder::class,
            RolePermissionServiceFeatureSeeder::class,
            SuperadminSeeder::class,
            SyncUserRoleSeeder::class,
        ]);
    }
}
