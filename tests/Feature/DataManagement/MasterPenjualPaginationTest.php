<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DataManagement\Models\MasterPenjual;
use Modules\IAMService\Database\Seeders\IAMServiceDatabaseSeeder;
use Modules\IAMService\Models\User;
use Shared\Constants\PaginationConstantsHelper;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'Modules/IAMService/database/migrations', '--realpath' => true]);
    $this->artisan('migrate', ['--path' => 'Modules/DataManagement/database/migrations', '--realpath' => true]);
    $this->seed(IAMServiceDatabaseSeeder::class);

    $this->admin = User::query()
        ->where('username', config('iamservice.superadmin.username'))
        ->firstOrFail();

    $this->token = auth('api')->login($this->admin);

    foreach (range(1, 20) as $index) {
        MasterPenjual::query()->create([
            'uuid' => generateUuid(),
            'nama_penjual' => sprintf('Penjual %02d', $index),
            'email' => sprintf('penjual%02d@example.com', $index),
            'no_hp' => null,
            'alamat' => null,
            'is_deleted' => false,
            'created_by' => 'test',
        ]);
    }
});

it('returns paginated master penjual list with standardized pagination meta', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/data-management/master-penjual?page=2&per_page=5');

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('pagination.currentPage', 2)
        ->assertJsonPath('pagination.perPage', 5)
        ->assertJsonPath('pagination.total', 20)
        ->assertJsonPath('pagination.lastPage', 4)
        ->assertJsonPath('pagination.from', 6)
        ->assertJsonPath('pagination.to', 10)
        ->assertJsonCount(5, 'datas.master_penjual')
        ->assertJsonStructure([
            'pagination' => [
                PaginationConstantsHelper::CURRENT_PAGE,
                PaginationConstantsHelper::PER_PAGE,
                PaginationConstantsHelper::TOTAL,
                PaginationConstantsHelper::LAST_PAGE,
                PaginationConstantsHelper::FROM,
                PaginationConstantsHelper::TO,
            ],
            'datas' => [
                'master_penjual' => [
                    ['uuid', 'nama_penjual', 'email', 'no_hp', 'alamat'],
                ],
            ],
        ]);
});

it('rejects invalid per_page for master penjual index', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/data-management/master-penjual?per_page=0');

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed');
});

it('filters master penjual by search on nama_penjual and email', function () {
    MasterPenjual::query()->create([
        'uuid' => generateUuid(),
        'nama_penjual' => 'Hangga Hendrawan',
        'email' => 'hangga@desahub.test',
        'no_hp' => null,
        'alamat' => null,
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    MasterPenjual::query()->create([
        'uuid' => generateUuid(),
        'nama_penjual' => 'Putri Ayu',
        'email' => 'contact.hangga@example.com',
        'no_hp' => null,
        'alamat' => null,
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    $byName = $this->withToken($this->token)
        ->getJson('/api/v1/data-management/master-penjual?search=Hangga Hendrawan');

    $byName->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('datas.master_penjual.0.nama_penjual', 'Hangga Hendrawan');

    $byEmail = $this->withToken($this->token)
        ->getJson('/api/v1/data-management/master-penjual?search=contact.hangga');

    $byEmail->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('datas.master_penjual.0.nama_penjual', 'Putri Ayu');

    $byPartial = $this->withToken($this->token)
        ->getJson('/api/v1/data-management/master-penjual?search=hangga');

    $byPartial->assertOk()
        ->assertJsonPath('pagination.total', 2);
});
