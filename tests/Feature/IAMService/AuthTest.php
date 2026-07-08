<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAMService\Database\Seeders\RoleSeeder;
use Modules\IAMService\Models\Role;
use Modules\IAMService\Models\User;
use Modules\IAMService\Services\UserRoleService;
use Shared\Constants\AvailableRoleConstantsHelper;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'Modules/IAMService/database/migrations', '--realpath' => true]);
    $this->seed(RoleSeeder::class);
});

it('registers a new user', function () {
    $response = $this->postJson('/api/v1/iam-services/auth/register', [
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.user.username', 'johndoe')
        ->assertJsonPath('datas.user.role', AvailableRoleConstantsHelper::USER)
        ->assertJsonPath('datas.user.roles', [AvailableRoleConstantsHelper::USER])
        ->assertJsonStructure(['datas' => ['token', 'user' => ['uuid', 'username', 'email', 'roles']]]);

    $this->assertDatabaseHas('user', [
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'role' => AvailableRoleConstantsHelper::USER,
        'is_active' => true,
        'is_deleted' => false,
    ]);

    $user = User::query()->where('username', 'johndoe')->first();
    $role = Role::query()->where('role', AvailableRoleConstantsHelper::USER)->first();

    $this->assertDatabaseHas('user_role', [
        'uuid_user' => $user->uuid,
        'uuid_role' => $role->uuid,
        'is_active' => true,
        'is_deleted' => false,
    ]);
});

it('registers a new user with custom role', function () {
    $response = $this->postJson('/api/v1/iam-services/auth/register', [
        'username' => 'wargauser',
        'email' => 'warga@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => AvailableRoleConstantsHelper::WARGA,
    ]);

    $response->assertOk()
        ->assertJsonPath('datas.user.role', AvailableRoleConstantsHelper::WARGA)
        ->assertJsonPath('datas.user.roles', [AvailableRoleConstantsHelper::WARGA]);

    $user = User::query()->where('username', 'wargauser')->first();
    $role = Role::query()->where('role', AvailableRoleConstantsHelper::WARGA)->first();

    $this->assertDatabaseHas('user_role', [
        'uuid_user' => $user->uuid,
        'uuid_role' => $role->uuid,
    ]);
});

it('rejects invalid role on register', function () {
    $response = $this->postJson('/api/v1/iam-services/auth/register', [
        'username' => 'invalidrole',
        'email' => 'invalid@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'INVALID_ROLE',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed');
});

it('validates register payload', function () {
    $response = $this->postJson('/api/v1/iam-services/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed');
});

it('logs in with valid credentials', function () {
    $user = User::create([
        'uuid' => generateUuid(),
        'username' => 'janedoe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'role' => AvailableRoleConstantsHelper::USER,
        'is_active' => true,
        'is_deleted' => false,
        'created_by' => 'test',
        'updated_by' => 'test',
    ]);

    app(UserRoleService::class)->assignRoleToUser($user, AvailableRoleConstantsHelper::USER, 'test');

    $response = $this->postJson('/api/v1/iam-services/auth/login', [
        'username' => 'janedoe',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.user.username', 'janedoe')
        ->assertJsonPath('datas.user.roles', [AvailableRoleConstantsHelper::USER])
        ->assertJsonStructure(['datas' => ['token']]);
});

it('rejects invalid login credentials', function () {
    $response = $this->postJson('/api/v1/iam-services/auth/login', [
        'username' => 'unknown',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('result', 'unauthorized');
});

it('logs out authenticated user', function () {
    $user = User::create([
        'uuid' => generateUuid(),
        'username' => 'logoutuser',
        'email' => 'logout@example.com',
        'password' => 'password123',
        'role' => AvailableRoleConstantsHelper::USER,
        'is_active' => true,
        'is_deleted' => false,
        'created_by' => 'test',
        'updated_by' => 'test',
    ]);

    app(UserRoleService::class)->assignRoleToUser($user, AvailableRoleConstantsHelper::USER, 'test');

    $token = auth('api')->login($user);

    $response = $this->postJson('/api/v1/iam-services/auth/logout', [], [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertOk()
        ->assertJsonPath('result', 'success');
});

it('backfills user role from legacy role column', function () {
    $user = User::create([
        'uuid' => generateUuid(),
        'username' => 'legacyuser',
        'email' => 'legacy@example.com',
        'password' => 'password123',
        'role' => AvailableRoleConstantsHelper::ADMIN,
        'is_active' => true,
        'is_deleted' => false,
        'created_by' => 'test',
        'updated_by' => 'test',
    ]);

    app(UserRoleService::class)->syncUserRoleFromLegacyColumn($user, 'test');

    $role = Role::query()->where('role', AvailableRoleConstantsHelper::ADMIN)->first();

    $this->assertDatabaseHas('user_role', [
        'uuid_user' => $user->uuid,
        'uuid_role' => $role->uuid,
    ]);
});
