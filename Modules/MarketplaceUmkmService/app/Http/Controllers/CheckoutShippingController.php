<?php

namespace Modules\MarketplaceUmkmService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\MarketplaceUmkmService\Http\Requests\UpdateCheckoutShippingRequest;
use Modules\MarketplaceUmkmService\Services\CheckoutShippingService;
use Shared\Constants\ResponseTypeConstantsHelper;

class CheckoutShippingController extends Controller
{
    public function __construct(private CheckoutShippingService $checkoutShippingService) {}

    public function upsert(UpdateCheckoutShippingRequest $request, string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Data pengiriman berhasil disimpan')
            ->detail('Data pengiriman berhasil disimpan')
            ->data($this->checkoutShippingService->upsert($uuid, $request->validated()))
            ->response();
    }

    public function show(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Data pengiriman berhasil diambil')
            ->detail('Data pengiriman berhasil diambil')
            ->data($this->checkoutShippingService->show($uuid))
            ->response();
    }
}
