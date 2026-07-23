<?php

namespace Modules\MarketplaceUmkmService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\MarketplaceUmkmService\Services\CheckoutPaymentService;
use Shared\Constants\ResponseTypeConstantsHelper;

class CheckoutPaymentController extends Controller
{
    public function __construct(private CheckoutPaymentService $checkoutPaymentService) {}

    public function store(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Pembayaran berhasil dibuat')
            ->detail('Pembayaran berhasil dibuat')
            ->data($this->checkoutPaymentService->create($uuid))
            ->response();
    }

    public function show(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Data pembayaran berhasil diambil')
            ->detail('Data pembayaran berhasil diambil')
            ->data($this->checkoutPaymentService->show($uuid))
            ->response();
    }
}
