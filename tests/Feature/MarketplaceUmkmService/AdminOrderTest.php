<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DataManagement\Models\MasterKategori;
use Modules\DataManagement\Models\MasterPenjual;
use Modules\IAMService\Database\Seeders\RoleSeeder;
use Modules\IAMService\Models\User;
use Modules\IAMService\Services\UserRoleService;
use Modules\MarketplaceService\Models\Product;
use Modules\MarketplaceUmkmService\Models\Checkout;
use Modules\MarketplaceUmkmService\Models\CheckoutShipping;
use Shared\Constants\AvailableRoleConstantsHelper;
use Shared\Constants\CheckoutStatusConstantsHelper;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'Modules/IAMService/database/migrations', '--realpath' => true]);
    $this->artisan('migrate', ['--path' => 'Modules/DataManagement/database/migrations', '--realpath' => true]);
    $this->artisan('migrate', ['--path' => 'Modules/MarketplaceService/database/migrations', '--realpath' => true]);
    $this->artisan('migrate', ['--path' => 'Modules/MarketplaceUmkmService/database/migrations', '--realpath' => true]);
    $this->seed(RoleSeeder::class);

    $this->admin = User::create([
        'uuid' => generateUuid(),
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'role' => AvailableRoleConstantsHelper::ADMIN,
        'is_active' => true,
        'is_deleted' => false,
        'created_by' => 'test',
        'updated_by' => 'test',
    ]);

    app(UserRoleService::class)->assignRoleToUser($this->admin, AvailableRoleConstantsHelper::ADMIN, 'test');

    $login = $this->postJson('/api/v1/iam-services/auth/login', [
        'username' => 'admin',
        'password' => 'password123',
    ]);

    $this->adminToken = $login->json('datas.token');

    $this->buyer = User::create([
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

function createPaidCheckoutWithShipping(): Checkout
{
    $checkout = Checkout::query()->create([
        'uuid' => generateUuid(),
        'uuid_user' => test()->buyer->uuid,
        'uuid_cart' => generateUuid(),
        'total_items' => 1,
        'total_price' => 80000,
        'status' => CheckoutStatusConstantsHelper::PAID,
        'is_deleted' => false,
        'created_by' => test()->buyer->uuid,
    ]);

    CheckoutShipping::query()->create([
        'uuid' => generateUuid(),
        'uuid_checkout' => $checkout->uuid,
        'nama_penerima' => 'Putri',
        'no_hp_penerima' => '08123456789',
        'alamat_penerima' => 'Jl. Merdeka No. 1',
        'is_deleted' => false,
        'created_by' => test()->buyer->uuid,
    ]);

    return $checkout;
}

it('processes paid order to processing', function () {
    $checkout = createPaidCheckoutWithShipping();

    $this->withToken($this->adminToken)
        ->postJson('/api/v1/marketplace-umkm-service/admin/orders/'.$checkout->uuid.'/process')
        ->assertOk()
        ->assertJsonPath('datas.status', CheckoutStatusConstantsHelper::PROCESSING);
});

it('rejects cancel when order already shipped with validation payload', function () {
    $checkout = createPaidCheckoutWithShipping();
    $checkout->update(['status' => CheckoutStatusConstantsHelper::SHIPPED]);

    $this->withToken($this->adminToken)
        ->postJson('/api/v1/marketplace-umkm-service/admin/orders/'.$checkout->uuid.'/cancel', [
            'reason' => 'Stok habis',
        ])
        ->assertStatus(422)
        ->assertJsonPath('result', 'failed')
        ->assertJsonPath('message', 'Validation failed')
        ->assertJsonFragment([
            'Pesanan tidak dapat dibatalkan pada status saat ini. Status saat ini: shipped (Dikirim). Status yang diizinkan: pending, paid, processing.',
        ]);
});

it('ships processing order with tracking', function () {
    $checkout = createPaidCheckoutWithShipping();
    $checkout->update(['status' => CheckoutStatusConstantsHelper::PROCESSING]);

    $this->withToken($this->adminToken)
        ->postJson('/api/v1/marketplace-umkm-service/admin/orders/'.$checkout->uuid.'/ship', [
            'courier' => 'JNE',
            'tracking_number' => 'JNE123456',
        ])
        ->assertOk()
        ->assertJsonPath('datas.status', CheckoutStatusConstantsHelper::SHIPPED)
        ->assertJsonPath('datas.shipping.courier', 'JNE')
        ->assertJsonPath('datas.shipping.tracking_number', 'JNE123456');
});
