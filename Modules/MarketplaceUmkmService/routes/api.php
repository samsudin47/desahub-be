<?php

use Illuminate\Support\Facades\Route;
use Modules\MarketplaceUmkmService\Http\Controllers\CartController;
use Modules\MarketplaceUmkmService\Http\Controllers\CheckoutController;
use Modules\MarketplaceUmkmService\Http\Controllers\CheckoutPaymentController;
use Modules\MarketplaceUmkmService\Http\Controllers\CheckoutShippingController;
use Modules\MarketplaceUmkmService\Http\Controllers\MidtransNotificationController;
use Modules\MarketplaceUmkmService\Http\Controllers\OrderController;
use Modules\MarketplaceUmkmService\Http\Controllers\ProductCategoriesController;
use Shared\Constants\AvailableRoleConstantsHelper;
use Shared\Constants\MiddlewareConstantsHelper;

Route::prefix('v1/marketplace-umkm-service')->group(function () {
    Route::post('midtrans/notification', MidtransNotificationController::class)
        ->name('marketplace-umkm-service.midtrans.notification');

    Route::middleware([
        MiddlewareConstantsHelper::DESAHUB_AUTH_API,
        sprintf(
            '%s:%s,%s',
            MiddlewareConstantsHelper::DESAHUB_USER_ROLE,
            AvailableRoleConstantsHelper::USER,
            AvailableRoleConstantsHelper::WARGA,
        ),
    ])->group(function () {
        Route::get('product-categories/{uuid}', [ProductCategoriesController::class, 'show'])
            ->name('marketplace-umkm-service.product-categories.show');

        Route::get('cart', [CartController::class, 'show'])
            ->name('marketplace-umkm-service.cart.show');

        Route::post('cart/items', [CartController::class, 'storeItem'])
            ->name('marketplace-umkm-service.cart.items.store');

        Route::put('cart/items/{uuid}', [CartController::class, 'updateItem'])
            ->name('marketplace-umkm-service.cart.items.update');

        Route::post('cart/items/{uuid}/plus', [CartController::class, 'plusOrderItem'])
            ->name('marketplace-umkm-service.cart.items.plus');

        Route::post('cart/items/{uuid}/minus', [CartController::class, 'minusOrderItem'])
            ->name('marketplace-umkm-service.cart.items.minus');

        Route::delete('cart/items/{uuid}', [CartController::class, 'destroyItem'])
            ->name('marketplace-umkm-service.cart.items.destroy');

        Route::delete('cart', [CartController::class, 'destroy'])
            ->name('marketplace-umkm-service.cart.destroy');

        Route::post('cart/checkout', [CheckoutController::class, 'store'])
            ->name('marketplace-umkm-service.cart.checkout.store');

        Route::get('checkout/{uuid}', [CheckoutController::class, 'show'])
            ->name('marketplace-umkm-service.checkout.show');

        Route::post('checkout/{uuid}/cancel', [CheckoutController::class, 'cancel'])
            ->name('marketplace-umkm-service.checkout.cancel');

        Route::put('checkout/{uuid}/shipping', [CheckoutShippingController::class, 'upsert'])
            ->name('marketplace-umkm-service.checkout.shipping.upsert');

        Route::get('checkout/{uuid}/shipping', [CheckoutShippingController::class, 'show'])
            ->name('marketplace-umkm-service.checkout.shipping.show');

        Route::post('checkout/{uuid}/pay', [CheckoutPaymentController::class, 'store'])
            ->name('marketplace-umkm-service.checkout.payment.store');

        Route::get('checkout/{uuid}/payment', [CheckoutPaymentController::class, 'show'])
            ->name('marketplace-umkm-service.checkout.payment.show');

        Route::get('orders', [OrderController::class, 'index'])
            ->name('marketplace-umkm-service.orders.index');
    });
});
