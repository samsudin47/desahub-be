<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DataManagement\Models\MasterKategori;
use Modules\DataManagement\Models\MasterPenjual;
use Modules\IAMService\Database\Seeders\RoleSeeder;
use Modules\IAMService\Models\User;
use Modules\IAMService\Services\UserRoleService;
use Modules\MarketplaceService\Models\Product;
use Modules\MarketplaceUmkmService\Models\Checkout;
use Modules\MarketplaceUmkmService\Models\CheckoutPayment;
use Modules\MarketplaceUmkmService\Services\Midtrans\MidtransSnapClient;
use Shared\Constants\AvailableRoleConstantsHelper;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'Modules/IAMService/database/migrations', '--realpath' => true]);
    $this->artisan('migrate', ['--path' => 'Modules/DataManagement/database/migrations', '--realpath' => true]);
    $this->artisan('migrate', ['--path' => 'Modules/MarketplaceService/database/migrations', '--realpath' => true]);
    $this->artisan('migrate', ['--path' => 'Modules/MarketplaceUmkmService/database/migrations', '--realpath' => true]);
    $this->seed(RoleSeeder::class);

    config([
        'services.midtrans.server_key' => 'SB-Mid-server-test',
        'services.midtrans.client_key' => 'SB-Mid-client-test',
        'services.midtrans.is_production' => false,
    ]);

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

function createCheckoutWithShipping(): string
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

    $checkoutUuid = $checkout->json('datas.uuid');

    test()->withToken(test()->token)
        ->putJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/shipping', [
            'nama_penerima' => 'PU Putri',
            'no_hp_penerima' => '081234567890',
            'alamat_penerima' => 'Jl. Melati No. 12',
            'latitude' => -6.247554,
            'longitude' => 106.764442,
        ])
        ->assertOk();

    return $checkoutUuid;
}

function midtransSignature(string $orderId, string $statusCode, string $grossAmount): string
{
    return hash('sha512', $orderId.$statusCode.$grossAmount.config('services.midtrans.server_key'));
}

it('creates snap payment for pending checkout with shipping', function () {
    $checkoutUuid = createCheckoutWithShipping();

    $this->mock(MidtransSnapClient::class, function ($mock) {
        $mock->shouldReceive('createSnapToken')->once()->andReturn('snap-token-test');
        $mock->shouldReceive('clientKey')->andReturn('SB-Mid-client-test');
        $mock->shouldReceive('isProduction')->andReturn(false);
    });

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/pay');

    $response->assertOk()
        ->assertJsonPath('datas.snap_token', 'snap-token-test')
        ->assertJsonPath('datas.status', 'pending')
        ->assertJsonPath('datas.gross_amount', 80000);

    $this->assertDatabaseHas('checkout_payment', [
        'uuid_checkout' => $checkoutUuid,
        'snap_token' => 'snap-token-test',
        'status' => 'pending',
    ]);
});

it('reuses existing pending payment instead of creating a new snap token', function () {
    $checkoutUuid = createCheckoutWithShipping();

    $this->mock(MidtransSnapClient::class, function ($mock) {
        $mock->shouldReceive('createSnapToken')->once()->andReturn('snap-token-first');
        $mock->shouldReceive('clientKey')->andReturn('SB-Mid-client-test');
        $mock->shouldReceive('isProduction')->andReturn(false);
    });

    $first = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/pay')
        ->assertOk();

    $second = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/pay')
        ->assertOk();

    expect($second->json('datas.uuid'))->toBe($first->json('datas.uuid'));
    expect(CheckoutPayment::query()->where('uuid_checkout', $checkoutUuid)->count())->toBe(1);
});

it('rejects payment creation when shipping is missing', function () {
    $store = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/items', [
            'uuid_product' => $this->product->uuid,
            'quantity' => 1,
        ]);

    $checkoutUuid = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/cart/checkout', [
            'cart_item_uuids' => [$store->json('datas.items.0.uuid')],
        ])
        ->json('datas.uuid');

    $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/pay')
        ->assertStatus(400);
});

it('handles midtrans settlement notification and deducts stock', function () {
    $checkoutUuid = createCheckoutWithShipping();

    $this->mock(MidtransSnapClient::class, function ($mock) {
        $mock->shouldReceive('createSnapToken')->once()->andReturn('snap-token-test');
        $mock->shouldReceive('clientKey')->andReturn('SB-Mid-client-test');
        $mock->shouldReceive('isProduction')->andReturn(false);
        $mock->shouldReceive('isValidSignature')->once()->andReturn(true);
    });

    $pay = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/pay')
        ->assertOk();

    $orderId = $pay->json('datas.order_id');
    $grossAmount = (string) $pay->json('datas.gross_amount');

    $payload = [
        'order_id' => $orderId,
        'status_code' => '200',
        'gross_amount' => $grossAmount.'.00',
        'signature_key' => midtransSignature($orderId, '200', $grossAmount.'.00'),
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
        'payment_type' => 'bank_transfer',
        'transaction_id' => 'midtrans-trx-1',
        'va_numbers' => [
            [
                'bank' => 'bca',
                'va_number' => '8077701234567890',
            ],
        ],
    ];

    $this->postJson('/api/v1/marketplace-umkm-service/midtrans/notification', $payload)
        ->assertOk();

    expect(Checkout::query()->where('uuid', $checkoutUuid)->value('status'))->toBe('paid');
    expect(CheckoutPayment::query()->where('order_id', $orderId)->value('status'))->toBe('paid');
    expect(CheckoutPayment::query()->where('order_id', $orderId)->value('va_number'))->toBe('8077701234567890');
    expect($this->product->fresh()->stock)->toBe(9);
});

it('rejects invalid midtrans signature', function () {
    $checkoutUuid = createCheckoutWithShipping();

    $this->mock(MidtransSnapClient::class, function ($mock) {
        $mock->shouldReceive('createSnapToken')->once()->andReturn('snap-token-test');
        $mock->shouldReceive('clientKey')->andReturn('SB-Mid-client-test');
        $mock->shouldReceive('isProduction')->andReturn(false);
        $mock->shouldReceive('isValidSignature')->once()->andReturn(false);
    });

    $pay = $this->withToken($this->token)
        ->postJson('/api/v1/marketplace-umkm-service/checkout/'.$checkoutUuid.'/pay')
        ->assertOk();

    $this->postJson('/api/v1/marketplace-umkm-service/midtrans/notification', [
        'order_id' => $pay->json('datas.order_id'),
        'status_code' => '200',
        'gross_amount' => '80000.00',
        'signature_key' => 'invalid',
        'transaction_status' => 'settlement',
    ])->assertStatus(400);
});
