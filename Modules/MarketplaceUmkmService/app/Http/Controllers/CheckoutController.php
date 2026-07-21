<?php

namespace Modules\MarketplaceUmkmService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\MarketplaceUmkmService\Http\Requests\StoreCheckoutRequest;
use Modules\MarketplaceUmkmService\Services\CheckoutService;
use Shared\Constants\ResponseTypeConstantsHelper;

class CheckoutController extends Controller
{
    public function __construct(private CheckoutService $checkoutService) {}

    public function store(StoreCheckoutRequest $request): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Checkout berhasil dibuat')
            ->detail('Checkout berhasil dibuat')
            ->data($this->checkoutService->store($request->validated()))
            ->response();
    }

    public function show(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Data checkout berhasil diambil')
            ->detail('Data checkout berhasil diambil')
            ->data($this->checkoutService->show($uuid))
            ->response();
    }

    public function cancel(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Checkout berhasil dibatalkan')
            ->detail('Checkout berhasil dibatalkan')
            ->data($this->checkoutService->cancel($uuid))
            ->response();
    }
}
