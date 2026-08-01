<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DataManagement\Models\MasterKategori;
use Modules\DataManagement\Models\MasterPenjual;
use Modules\IAMService\Database\Seeders\IAMServiceDatabaseSeeder;
use Modules\IAMService\Models\User;
use Modules\MarketplaceService\Models\Product;
use Shared\Constants\PaginationConstantsHelper;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'Modules/IAMService/database/migrations', '--realpath' => true]);
    $this->artisan('migrate', ['--path' => 'Modules/DataManagement/database/migrations', '--realpath' => true]);
    $this->artisan('migrate', ['--path' => 'Modules/MarketplaceService/database/migrations', '--realpath' => true]);
    $this->seed(IAMServiceDatabaseSeeder::class);

    $this->admin = User::query()
        ->where('username', config('iamservice.superadmin.username'))
        ->firstOrFail();

    $this->token = auth('api')->login($this->admin);

    $this->kategoriMakanan = MasterKategori::query()->create([
        'uuid' => generateUuid(),
        'nama_kategori' => 'Makanan',
        'deskripsi' => 'Kategori makanan',
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    $this->kategoriMinuman = MasterKategori::query()->create([
        'uuid' => generateUuid(),
        'nama_kategori' => 'Minuman',
        'deskripsi' => 'Kategori minuman',
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    $this->penjualHangga = MasterPenjual::query()->create([
        'uuid' => generateUuid(),
        'nama_penjual' => 'Hangga Hendrawan',
        'email' => 'hangga@example.com',
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    $this->penjualMakIsah = MasterPenjual::query()->create([
        'uuid' => generateUuid(),
        'nama_penjual' => 'Mak Isah',
        'email' => 'makisah@example.com',
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    foreach (range(1, 20) as $index) {
        Product::query()->create([
            'uuid' => generateUuid(),
            'nama_product' => sprintf('Produk %02d', $index),
            'deskripsi' => 'Deskripsi produk '.$index,
            'harga' => 10000 * $index,
            'stock' => 10,
            'gambar' => null,
            'uuid_kategori' => $this->kategoriMakanan->uuid,
            'uuid_penjual' => $this->penjualHangga->uuid,
            'is_deleted' => false,
            'created_by' => 'test',
        ]);
    }
});

it('returns paginated product list with standardized pagination meta', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/marketplace-service/product?page=2&per_page=5');

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('pagination.currentPage', 2)
        ->assertJsonPath('pagination.perPage', 5)
        ->assertJsonPath('pagination.total', 20)
        ->assertJsonPath('pagination.lastPage', 4)
        ->assertJsonPath('pagination.from', 6)
        ->assertJsonPath('pagination.to', 10)
        ->assertJsonCount(5, 'datas.product')
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
                'product' => [
                    [
                        'uuid',
                        'nama_product',
                        'deskripsi',
                        'harga',
                        'stock',
                        'gambar',
                        'uuid_kategori',
                        'nama_kategori',
                        'uuid_penjual',
                        'nama_penjual',
                    ],
                ],
            ],
        ]);
});

it('rejects invalid per_page for product index', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/marketplace-service/product?per_page=0');

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed');
});

it('filters products by search on product, kategori, and penjual', function () {
    Product::query()->create([
        'uuid' => generateUuid(),
        'nama_product' => 'Es Teh Manis',
        'deskripsi' => 'Minuman dingin',
        'harga' => 5000,
        'stock' => 10,
        'gambar' => null,
        'uuid_kategori' => $this->kategoriMinuman->uuid,
        'uuid_penjual' => $this->penjualMakIsah->uuid,
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    Product::query()->create([
        'uuid' => generateUuid(),
        'nama_product' => 'Soto Lamongan',
        'deskripsi' => 'Hidangan spesial Mak Isah',
        'harga' => 15000,
        'stock' => 8,
        'gambar' => null,
        'uuid_kategori' => $this->kategoriMakanan->uuid,
        'uuid_penjual' => $this->penjualMakIsah->uuid,
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    $byProduct = $this->withToken($this->token)
        ->getJson('/api/v1/marketplace-service/product?search=Es Teh Manis');

    $byProduct->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('datas.product.0.nama_product', 'Es Teh Manis');

    $byKategori = $this->withToken($this->token)
        ->getJson('/api/v1/marketplace-service/product?search=Minuman');

    $byKategori->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('datas.product.0.nama_kategori', 'Minuman');

    $byPenjual = $this->withToken($this->token)
        ->getJson('/api/v1/marketplace-service/product?search=Mak Isah');

    $byPenjual->assertOk()
        ->assertJsonPath('pagination.total', 2);
});
