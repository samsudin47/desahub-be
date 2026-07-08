<?php

namespace Modules\IAMService\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAMService\Models\User;
use Modules\IAMService\Services\UserRoleService;
use Shared\Constants\AvailableRoleConstantsHelper;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $username = (string) config('iamservice.superadmin.username');
        $email = (string) config('iamservice.superadmin.email');
        $password = (string) config('iamservice.superadmin.password');

        $user = User::query()->updateOrCreate(
            ['username' => $username],
            [
                'uuid' => User::query()->where('username', $username)->value('uuid') ?? generateUuid(),
                'email' => $email,
                'password' => $password,
                'role' => AvailableRoleConstantsHelper::SUPERADMIN,
                'is_active' => true,
                'is_deleted' => false,
                'last_activity_at' => dateNow(),
                'created_by' => 'seeder',
                'updated_by' => 'seeder',
            ]
        );

        app(UserRoleService::class)->assignRoleToUser(
            $user,
            AvailableRoleConstantsHelper::SUPERADMIN,
            'seeder'
        );
    }
}
