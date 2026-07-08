<?php

namespace Modules\IAMService\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAMService\Services\UserRoleService;

class SyncUserRoleSeeder extends Seeder
{
    public function run(): void
    {
        app(UserRoleService::class)->backfillAllUsers('sync-seeder');
    }
}
