<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DataManagement\Models\MasterKategori;
use Modules\DataManagement\Models\MasterPenjual;
use Modules\IAMService\Database\Seeders\RoleSeeder;
use Modules\IAMService\Models\User;
use Modules\IAMService\Services\UserRoleService;
use Modules\MarketplaceService\Models\Product;
use Modules\MarketplaceUmkmService\Models\Checkout;
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

function createPendingCheckoutForUser(): string
{
    $store = test()->withToken(test()->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => test()->product->uuid,
            'quantity' => 1,
        ]);

    $cartItemUuid = $store->json('datas.items.0.uuid');

    $checkout = test()->withToken(test()->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$cartItemUuid],
        ]);

    return $checkout->json('datas.uuid');
}

it('creates checkout shipping for pending checkout', function () {
    $checkoutUuid = createPendingCheckoutForUser();

    $response = $this->withToken($this->token)
        ->putJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/shipping', [
            'nama_penerima' => 'PU Putri',
            'no_hp_penerima' => '081234567890',
            'alamat_penerima' => 'Jl. Melati No. 12, RT 02/RW 01, Desa Sukamaju',
            'latitude' => -6.247554,
            'longitude' => 106.764442,
        ]);

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.uuid_checkout', $checkoutUuid)
        ->assertJsonPath('datas.nama_penerima', 'PU Putri')
        ->assertJsonPath('datas.no_hp_penerima', '081234567890')
        ->assertJsonPath('datas.alamat_penerima', 'Jl. Melati No. 12, RT 02/RW 01, Desa Sukamaju');

    $this->assertDatabaseHas('checkout_shipping', [
        'uuid_checkout' => $checkoutUuid,
        'nama_penerima' => 'PU Putri',
        'no_hp_penerima' => '081234567890',
        'is_deleted' => false,
    ]);
});

it('updates existing checkout shipping', function () {
    $checkoutUuid = createPendingCheckoutForUser();

    $this->withToken($this->token)
        ->putJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/shipping', [
            'nama_penerima' => 'PU Putri',
            'no_hp_penerima' => '081234567890',
            'alamat_penerima' => 'Alamat lama',
        ]);

    $response = $this->withToken($this->token)
        ->putJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/shipping', [
            'nama_penerima' => 'Putri Updated',
            'no_hp_penerima' => '08999888777',
            'alamat_penerima' => 'Alamat baru',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

    $response->assertOk()
        ->assertJsonPath('datas.nama_penerima', 'Putri Updated')
        ->assertJsonPath('datas.no_hp_penerima', '08999888777')
        ->assertJsonPath('datas.alamat_penerima', 'Alamat baru');

    $this->assertDatabaseCount('checkout_shipping', 1);
});

it('shows checkout shipping detail', function () {
    $checkoutUuid = createPendingCheckoutForUser();

    $this->withToken($this->token)
        ->putJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/shipping', [
            'nama_penerima' => 'PU Putri',
            'no_hp_penerima' => '081234567890',
            'alamat_penerima' => 'Jl. Melati No. 12',
        ]);

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/shipping');

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.uuid_checkout', $checkoutUuid)
        ->assertJsonPath('datas.nama_penerima', 'PU Putri');
});

it('validates required shipping fields', function () {
    $checkoutUuid = createPendingCheckoutForUser();

    $response = $this->withToken($this->token)
        ->putJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/shipping', []);

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed');
});

it('rejects shipping update when checkout is not pending', function () {
    $checkoutUuid = createPendingCheckoutForUser();

    Checkout::query()->where('uuid', $checkoutUuid)->update(['status' => 'paid']);

    $response = $this->withToken($this->token)
        ->putJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/shipping', [
            'nama_penerima' => 'PU Putri',
            'no_hp_penerima' => '081234567890',
            'alamat_penerima' => 'Jl. Melati No. 12',
        ]);

    $response->assertStatus(400)
        ->assertJsonPath('result', 'failed');
});
