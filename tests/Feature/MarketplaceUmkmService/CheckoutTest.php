<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DataManagement\Models\MasterKategori;
use Modules\DataManagement\Models\MasterPenjual;
use Modules\IAMService\Database\Seeders\RoleSeeder;
use Modules\IAMService\Models\User;
use Modules\IAMService\Services\UserRoleService;
use Modules\MarketplaceService\Models\Product;
use Modules\MarketplaceUmkmService\Models\Checkout;
use Modules\MarketplaceUmkmService\Models\CheckoutItem;
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

it('creates checkout from selected cart items', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 2,
        ]);

    $cartItemUuid = $store->json('datas.items.0.uuid');

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$cartItemUuid],
        ]);

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.status', 'pending')
        ->assertJsonPath('datas.total_item', 2)
        ->assertJsonPath('datas.total_harga', 160000)
        ->assertJsonPath('datas.items.0.harga_satuan', 80000)
        ->assertJsonPath('datas.items.0.subtotal', 160000)
        ->assertJsonPath('datas.items.0.produk.nama_produk', 'Baju Pria');

    $checkoutUuid = $response->json('datas.uuid');

    $this->assertDatabaseHas('checkout', [
        'uuid' => $checkoutUuid,
        'uuid_user' => $this->user->uuid,
        'total_items' => '2',
        'total_price' => '160000',
        'status' => 'pending',
        'is_deleted' => false,
    ]);

    $this->assertDatabaseHas('checkout_item', [
        'uuid_checkout' => $checkoutUuid,
        'uuid_cart_item' => $cartItemUuid,
        'uuid_product' => $this->product->uuid,
        'quantity' => 2,
        'harga_satuan' => '80000',
        'subtotal' => '160000',
        'is_deleted' => false,
    ]);

    $this->assertDatabaseHas('cart_item', [
        'uuid' => $cartItemUuid,
        'is_deleted' => true,
    ]);
});

it('shows checkout detail for authenticated user', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 1,
        ]);

    $cartItemUuid = $store->json('datas.items.0.uuid');

    $checkout = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$cartItemUuid],
        ]);

    $checkoutUuid = $checkout->json('datas.uuid');

    $response = $this->withToken($this->token)
        ->getJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid);

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.uuid', $checkoutUuid)
        ->assertJsonPath('datas.total_item', 1)
        ->assertJsonPath('datas.total_harga', 80000);
});

it('validates checkout payload requires cart item uuids', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', []);

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed');
});

it('rejects checkout when cart item uuid does not belong to user cart', function () {
    $otherUser = User::create([
        'uuid' => generateUuid(),
        'username' => 'budi',
        'email' => 'budi@example.com',
        'password' => 'password123',
        'role' => AvailableRoleConstantsHelper::USER,
        'is_active' => true,
        'is_deleted' => false,
        'created_by' => 'test',
        'updated_by' => 'test',
    ]);

    app(UserRoleService::class)->assignRoleToUser($otherUser, AvailableRoleConstantsHelper::USER, 'test');

    $checkout = Checkout::create([
        'uuid' => generateUuid(),
        'uuid_user' => $otherUser->uuid,
        'uuid_cart' => generateUuid(),
        'total_items' => 1,
        'total_price' => 80000,
        'status' => 'draft',
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    CheckoutItem::create([
        'uuid' => generateUuid(),
        'uuid_checkout' => $checkout->uuid,
        'uuid_cart_item' => generateUuid(),
        'uuid_product' => $this->product->uuid,
        'uuid_penjual' => $this->product->uuid_penjual,
        'quantity' => 1,
        'harga_satuan' => 80000,
        'subtotal' => 80000,
        'is_deleted' => false,
        'created_by' => 'test',
    ]);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [generateUuid()],
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed');
});

it('rejects checkout when stock is insufficient', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 10,
        ]);

    $cartItemUuid = $store->json('datas.items.0.uuid');

    $this->product->update(['stock' => 5]);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$cartItemUuid],
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed')
        ->assertJsonPath('validationFailed.0', 'Jumlah melebihi stok yang tersedia (5).');
});

it('rejects checkout when cart is empty', function () {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [generateUuid()],
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed');
});

it('rejects duplicate checkout for the same cart item', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 5,
        ]);

    $cartItemUuid = $store->json('datas.items.0.uuid');

    $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$cartItemUuid],
        ])
        ->assertOk();

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$cartItemUuid],
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed');
});

it('rejects checkout when pending reservations exceed available stock', function () {
    $firstStore = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 5,
        ]);

    $firstCartItemUuid = $firstStore->json('datas.items.0.uuid');

    $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$firstCartItemUuid],
        ])
        ->assertOk();

    $secondStore = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 6,
        ]);

    $secondCartItemUuid = $secondStore->json('datas.items.0.uuid');

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$secondCartItemUuid],
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('result', 'failed')
        ->assertJsonPath('validationFailed.0', 'Jumlah melebihi stok yang tersedia (5).');

    expect(Checkout::query()->notDeleted()->where('status', 'pending')->count())->toBe(1);
});

it('prevents repeated checkout beyond total stock through cart re-add flow', function () {
    for ($attempt = 1; $attempt <= 4; $attempt++) {
        $store = $this->withToken($this->token)
            ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
                'uuid_product' => $this->product->uuid,
                'quantity' => 5,
            ]);

        $cartItemUuid = $store->json('datas.items.0.uuid');

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
                'cart_item_uuids' => [$cartItemUuid],
            ]);

        if ($attempt <= 2) {
            $response->assertOk();
        } else {
            $response->assertStatus(422)
                ->assertJsonPath('result', 'failed');
        }
    }

    expect(Checkout::query()->notDeleted()->where('status', 'pending')->count())->toBe(2);
});

it('cancels pending checkout and restores cart items', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 2,
        ]);

    $cartItemUuid = $store->json('datas.items.0.uuid');

    $checkout = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$cartItemUuid],
        ]);

    $checkoutUuid = $checkout->json('datas.uuid');

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/cancel');

    $response->assertOk()
        ->assertJsonPath('result', 'success')
        ->assertJsonPath('datas.checkout.status', 'cancelled')
        ->assertJsonPath('datas.cart.total_item', 2)
        ->assertJsonPath('datas.cart.total_harga', 160000)
        ->assertJsonPath('datas.cart.items.0.uuid', $cartItemUuid);

    $this->assertDatabaseHas('checkout', [
        'uuid' => $checkoutUuid,
        'status' => 'cancelled',
        'is_deleted' => false,
    ]);

    $this->assertDatabaseHas('cart_item', [
        'uuid' => $cartItemUuid,
        'quantity' => 2,
        'is_deleted' => false,
    ]);
});

it('releases reserved stock after checkout is cancelled', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 7,
        ]);

    $cartItemUuid = $store->json('datas.items.0.uuid');

    $checkout = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$cartItemUuid],
        ])
        ->assertOk();

    $checkoutUuid = $checkout->json('datas.uuid');

    $cancelResponse = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/cancel')
        ->assertOk();

    $restoredCartItemUuid = $cancelResponse->json('datas.cart.items.0.uuid');

    $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$restoredCartItemUuid],
        ])
        ->assertOk();
});

it('rejects cancelling checkout that is not pending', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 1,
        ]);

    $cartItemUuid = $store->json('datas.items.0.uuid');

    $checkout = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$cartItemUuid],
        ]);

    $checkoutUuid = $checkout->json('datas.uuid');

    Checkout::query()->where('uuid', $checkoutUuid)->update(['status' => 'paid']);

    $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/cancel')
        ->assertStatus(400)
        ->assertJsonPath('result', 'failed');
});

it('rejects unauthenticated checkout access', function () {
    $this->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
        'cart_item_uuids' => [generateUuid()],
    ])->assertStatus(401);
});
