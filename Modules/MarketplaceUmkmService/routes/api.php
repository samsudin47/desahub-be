<?php

use Illuminate\Support\Facades\Route;
use Modules\MarketplaceUmkmService\Http\Controllers\CartController;
use Modules\MarketplaceUmkmService\Http\Controllers\CheckoutController;
use Modules\MarketplaceUmkmService\Http\Controllers\ProductCategoriesController;
use Shared\Constants\AvailableRoleConstantsHelper;
use Shared\Constants\MiddlewareConstantsHelper;

Route::prefix('v1/marketplace-umkm-service')->middleware([
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
});
