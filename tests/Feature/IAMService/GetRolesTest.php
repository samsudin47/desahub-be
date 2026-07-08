<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAMService\Database\Seeders\IAMServiceDatabaseSeeder;
use Modules\IAMService\Models\User;
use Modules\IAMService\Services\UserRoleService;
use Shared\Constants\AvailableRoleConstantsHelper;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'Modules/IAMService/database/migrations', '--realpath' => true]);
    $this->seed(IAMServiceDatabaseSeeder::class);
});

it('allows admin to get roles', function () {
    $user = User::create([
        'uuid' => generateUuid(),
        'username' => 'adminuser',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'role' => AvailableRoleConstantsHelper::ADMIN,
        'is_active' => true,
        'is_deleted' => false,
        'created_by' => 'test',
        'updated_by' => 'test',
    ]);

    app(UserRoleService::class)->assignRoleToUser($user, AvailableRoleConstantsHelper::ADMIN, 'test');

    $token = auth('api')->login($user);

    $response = $this->getJson('/api/v1/iam-services/role-base-access-control/roles', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.roles.0.role', AvailableRoleConstantsHelper::ADMIN)
        ->assertJsonStructure(['datas' => ['roles' => [['uuid', 'role', 'name', 'description', 'is_system']]]]);
});

it('allows public access to get roles without token', function () {
    $response = $this->getJson('/api/v1/iam-services/roles');

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.roles.0.role', AvailableRoleConstantsHelper::ADMIN)
        ->assertJsonStructure(['datas' => ['roles' => [['uuid', 'role', 'name', 'description', 'is_system']]]]);
});

it('forbids user without admin or superadmin role from getting roles', function () {
    $user = User::create([
        'uuid' => generateUuid(),
        'username' => 'regularuser',
        'email' => 'user@example.com',
        'password' => 'password123',
        'role' => AvailableRoleConstantsHelper::USER,
        'is_active' => true,
        'is_deleted' => false,
        'created_by' => 'test',
        'updated_by' => 'test',
    ]);

    app(UserRoleService::class)->assignRoleToUser($user, AvailableRoleConstantsHelper::USER, 'test');

    $token = auth('api')->login($user);

    $response = $this->getJson('/api/v1/iam-services/role-base-access-control/roles', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertForbidden()
        ->assertJsonPath('result', 'forbidden');
});
