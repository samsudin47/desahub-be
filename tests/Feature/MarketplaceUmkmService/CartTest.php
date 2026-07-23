<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DataManagement\Models\MasterKategori;
use Modules\DataManagement\Models\MasterPenjual;
use Modules\IAMService\Database\Seeders\RoleSeeder;
use Modules\IAMService\Models\User;
use Modules\IAMService\Services\UserRoleService;
use Modules\MarketplaceService\Models\Product;
use Modules\MarketplaceUmkmService\Models\Cart;
use Modules\MarketplaceUmkmService\Models\CartItem;
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

it('returns empty cart for authenticated user', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/marketplace-umkm-service/cart');

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.total_item', 0)
        ->assertJsonPath('datas.total_harga', 0)
        ->assertJsonPath('datas.items', []);
});

it('adds product to cart when clicking tambah', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 1,
        ]);

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.total_item', 1)
        ->assertJsonPath('datas.total_harga', 80000)
        ->assertJsonPath('datas.items.0.produk.nama_produk', 'Baju Pria')
        ->assertJsonPath('datas.items.0.produk.stock', 10)
        ->assertJsonPath('datas.items.0.produk.sisa_stock', 9)
        ->assertJsonPath('datas.items.0.produk.penjual.nama', 'Hangga Hendrawan');

    $this->assertDatabaseHas('cart', [
        'uuid_user' => $this->user->uuid,
        'is_deleted' => false,
    ]);

    $this->assertDatabaseHas('cart_item', [
        'uuid_product' => $this->product->uuid,
        'quantity' => 1,
        'is_deleted' => false,
    ]);
});

it('increments quantity when same product is added again', function () {
    $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 1,
        ]);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 2,
        ]);

    $response->assertOk()
        ->assertJsonPath('datas.total_item', 3)
        ->assertJsonPath('datas.items.0.quantity', 3)
        ->assertJsonPath('datas.items.0.produk.sisa_stock', 7);

    expect(CartItem::query()->notDeleted()->count())->toBe(1);
});

it('rejects add when quantity exceeds stock', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 11,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed')
        ->assertJsonPath('validationFailed.0', 'Jumlah melebihi stok yang tersedia (10).');
});

it('rejects add when product stock is empty', function () {
    $this->product->update(['stock' => 0]);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 1,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed')
        ->assertJsonPath('validationFailed.0', 'Stok produk sudah habis.');
});

it('updates cart item quantity', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 1,
        ]);

    $itemUuid = $store->json('datas.items.0.uuid');

    $response = $this->withToken($this->token)
        ->putJson('/api/v1/marketplace-umkm-service/cart/items/'.$itemUuid, [
            'quantity' => 4,
        ]);

    $response->assertOk()
        ->assertJsonPath('datas.total_item', 4)
        ->assertJsonPath('datas.total_harga', 320000)
        ->assertJsonPath('datas.items.0.produk.sisa_stock', 6);
});

it('increments cart item quantity via plus endpoint', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 1,
        ]);

    $itemUuid = $store->json('datas.items.0.uuid');

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items/'.$itemUuid.'/plus');

    $response->assertOk()
        ->assertJsonPath('datas.items.0.quantity', 2)
        ->assertJsonPath('datas.total_item', 2)
        ->assertJsonPath('datas.total_harga', 160000)
        ->assertJsonPath('datas.items.0.produk.sisa_stock', 8);
});

it('decrements cart item quantity via minus endpoint', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 3,
        ]);

    $itemUuid = $store->json('datas.items.0.uuid');

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items/'.$itemUuid.'/minus');

    $response->assertOk()
        ->assertJsonPath('datas.items.0.quantity', 2)
        ->assertJsonPath('datas.total_item', 2)
        ->assertJsonPath('datas.total_harga', 160000)
        ->assertJsonPath('datas.items.0.produk.sisa_stock', 8);
});

it('removes cart item when minus reaches one', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 1,
        ]);

    $itemUuid = $store->json('datas.items.0.uuid');

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items/'.$itemUuid.'/minus');

    $response->assertOk()
        ->assertJsonPath('datas.total_item', 0)
        ->assertJsonPath('datas.items', []);

    $this->assertDatabaseHas('cart_item', [
        'uuid' => $itemUuid,
        'is_deleted' => true,
    ]);
});

it('rejects plus when quantity exceeds stock', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 10,
        ]);

    $itemUuid = $store->json('datas.items.0.uuid');

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items/'.$itemUuid.'/plus');

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed')
        ->assertJsonPath('validationFailed.0', 'Jumlah melebihi stok yang tersedia (10).');
});

it('soft deletes cart item', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 1,
        ]);

    $itemUuid = $store->json('datas.items.0.uuid');

    $response = $this->withToken($this->token)
        ->deleteJson('/api/v1/marketplace-umkm-service/cart/items/'.$itemUuid);

    $response->assertOk()
        ->assertJsonPath('datas.total_item', 0)
        ->assertJsonPath('datas.items', []);

    $this->assertDatabaseHas('cart_item', [
        'uuid' => $itemUuid,
        'is_deleted' => true,
    ]);
});

it('clears entire cart', function () {
    $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 1,
        ]);

    $response = $this->withToken($this->token)
        ->deleteJson('/api/v1/marketplace-umkm-service/cart');

    $response->assertOk()
        ->assertJsonPath('result', 'success');

    expect(Cart::query()->notDeleted()->where('uuid_user', $this->user->uuid)->exists())->toBeFalse();
    expect(CartItem::query()->notDeleted()->count())->toBe(0);
});

it('validates store cart item payload', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', []);

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed');
});

it('rejects unauthenticated cart access', function () {
    $this->getJson('/api/v1/marketplace-umkm-service/cart')
        ->assertStatus(401);
});
