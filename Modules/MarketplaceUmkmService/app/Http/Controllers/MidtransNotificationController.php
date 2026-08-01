<?php

namespace Modules\MarketplaceUmkmService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\MarketplaceUmkmService\Services\CheckoutPaymentService;
use Shared\Constants\ResponseTypeConstantsHelper;

class MidtransNotificationController extends Controller
{
    public function __construct(private CheckoutPaymentService $checkoutPaymentService) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->all();

        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Notifikasi Midtrans berhasil diterima')
            ->detail('Notifikasi Midtrans berhasil diterima')
            ->data($this->checkoutPaymentService->handleNotification($payload, $request->ip()))
            ->response();
    }
}
