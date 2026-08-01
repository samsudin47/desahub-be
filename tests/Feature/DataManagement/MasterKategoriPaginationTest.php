<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DataManagement\Models\MasterKategori;
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
        MasterKategori::query()->create([
            'uuid' => generateUuid(),
            'nama_kategori' => sprintf('Kategori %02d', $index),
            'deskripsi' => 'Deskripsi '.$index,
            'is_deleted' => false,
            'created_by' => 'test',
        ]);
    }
});

it('returns paginated master kategori list with standardized pagination meta', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/data-management/master-kategori?page=2&per_page=5');

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('pagination.currentPage', 2)
        ->assertJsonPath('pagination.perPage', 5)
        ->assertJsonPath('pagination.total', 20)
        ->assertJsonPath('pagination.lastPage', 4)
        ->assertJsonPath('pagination.from', 6)
        ->assertJsonPath('pagination.to', 10)
        ->assertJsonCount(5, 'datas.master_kategori')
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
                'master_kategori' => [
                    ['uuid', 'nama_kategori', 'deskripsi'],
                ],
            ],
        ]);
});

it('rejects invalid per_page for master kategori index', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/data-management/master-kategori?per_page=0');

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed');
});

it('filters master kategori by search on nama_kategori and deskripsi', function () {
    MasterKategori::query()->create([
        'uuid' => generateUuid(),
        'nama_kategori' => 'Elektronik Rumah',
        'deskripsi' => 'Peralatan dapur modern',
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    MasterKategori::query()->create([
        'uuid' => generateUuid(),
        'nama_kategori' => 'Fashion',
        'deskripsi' => 'Koleksi elektronik wearable',
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    $byName = $this->withToken($this->token)
        ->getJson('/api/v1/data-management/master-kategori?search=Elektronik Rumah');

    $byName->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('datas.master_kategori.0.nama_kategori', 'Elektronik Rumah');

    $byDescription = $this->withToken($this->token)
        ->getJson('/api/v1/data-management/master-kategori?search=wearable');

    $byDescription->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('datas.master_kategori.0.nama_kategori', 'Fashion');

    $byPartial = $this->withToken($this->token)
        ->getJson('/api/v1/data-management/master-kategori?search=elektronik');

    $byPartial->assertOk()
        ->assertJsonPath('pagination.total', 2);
});
