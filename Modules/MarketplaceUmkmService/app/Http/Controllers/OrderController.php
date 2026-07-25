<?php

namespace Modules\MarketplaceUmkmService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\MarketplaceUmkmService\Http\Requests\ListOrdersRequest;
use Modules\MarketplaceUmkmService\Services\OrderService;
use Shared\Constants\ResponseTypeConstantsHelper;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(ListOrdersRequest $request): JsonResponse
    {
        $status = $request->validated('status');

        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Daftar pesanan berhasil diambil')
            ->detail('Daftar pesanan berhasil diambil')
            ->data($this->orderService->list($status))
            ->response();
    }
}
