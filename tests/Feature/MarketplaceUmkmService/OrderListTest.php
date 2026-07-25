<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DataManagement\Models\MasterKategori;
use Modules\DataManagement\Models\MasterPenjual;
use Modules\IAMService\Database\Seeders\RoleSeeder;
use Modules\IAMService\Models\User;
use Modules\IAMService\Services\UserRoleService;
use Modules\MarketplaceService\Models\Product;
use Shared\Constants\AvailableRoleConstantsHelper;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'Modules/IAMService/database/migrations', '--realpath' => true]);
    $this->artisan('migrate', ['--path' => 'Modules/DataManagement/database/migrations', '--realpath' => true]);
    $this->artisan('migrate', ['--path' => 'Modules/MarketplaceService/database/migrations', '--realpath' => true]);
    $this->artisan('migrate', ['--path' => 'Modules/MarketplaceUmkmService/database/migrations', '--realpath' => true]);
    $this->seed(RoleSeeder::class);

    $this->user = User::create([
        'uuid' => generateUuid(),
        'username' => 'putri',
        'email' => 'putri@example.com',
        'password' => 'password123',
        'role' => AvailableRoleConstantsHelper::USER,
        'is_active' => true,
        'is_deleted' => false,
        'created_by' => 'test',
        'updated_by' => 'test',
    ]);

    app(UserRoleService::class)->assignRoleToUser($this->user, AvailableRoleConstantsHelper::USER, 'test');

    $login = $this->postJson('/api/v1/iam-services/auth/login', [
        'username' => 'putri',
        'password' => 'password123',
    ]);

    $this->token = $login->json('datas.token');

    $kategori = MasterKategori::create([
        'uuid' => generateUuid(),
        'nama_kategori' => 'Makanan',
        'deskripsi' => 'Kategori makanan',
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    $penjual = MasterPenjual::create([
        'uuid' => generateUuid(),
        'nama_penjual' => 'Hangga Hendrawan',
        'email' => 'hangga@example.com',
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    $this->product = Product::create([
        'uuid' => generateUuid(),
        'nama_product' => 'Baju Pria',
        'deskripsi' => 'Produk UMKM',
        'harga' => 80000,
        'stock' => 10,
        'gambar' => null,
        'uuid_kategori' => $kategori->uuid,
        'uuid_penjual' => $penjual->uuid,
        'is_deleted' => false,
        'created_by' => 'test',
    ]);
});

it('lists orders for authenticated user', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 1,
        ]);

    $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$store->json('datas.items.0.uuid')],
        ])
        ->assertOk();

    $this->withToken($this->token)
        ->getJson('/api/v1/marketplace-umkm-service/orders?status=pending')
        ->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.0.status', 'pending')
        ->assertJsonPath('datas.0.status_label', 'Menunggu Pembayaran')
        ->assertJsonPath('datas.0.total_harga', 80000);
});
