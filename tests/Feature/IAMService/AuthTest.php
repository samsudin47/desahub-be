<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAMService\Models\User;
use Shared\Constants\AvailableRoleConstantsHelper;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'Modules/IAMService/database/migrations', '--realpath' => true]);
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
        ->assertJsonStructure(['datas' => ['token', 'user' => ['uuid', 'username', 'email']]]);

    $this->assertDatabaseHas('user', [
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'role' => AvailableRoleConstantsHelper::USER,
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
        ->assertJsonPath('datas.user.role', AvailableRoleConstantsHelper::WARGA);

    $this->assertDatabaseHas('user', [
        'username' => 'wargauser',
        'role' => AvailableRoleConstantsHelper::WARGA,
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
    User::create([
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

    $response = $this->postJson('/api/v1/iam-services/auth/login', [
        'username' => 'janedoe',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.user.username', 'janedoe')
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

    $token = auth('api')->login($user);

    $response = $this->postJson('/api/v1/iam-services/auth/logout', [], [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertOk()
        ->assertJsonPath('result', 'success');
});
