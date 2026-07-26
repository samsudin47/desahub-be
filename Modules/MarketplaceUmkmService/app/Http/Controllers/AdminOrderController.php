<?php

namespace Modules\MarketplaceUmkmService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\MarketplaceUmkmService\Http\Requests\CancelAdminOrderRequest;
use Modules\MarketplaceUmkmService\Http\Requests\ListAdminOrdersRequest;
use Modules\MarketplaceUmkmService\Http\Requests\ShipAdminOrderRequest;
use Modules\MarketplaceUmkmService\Services\AdminOrderService;
use Shared\Constants\ResponseTypeConstantsHelper;

class AdminOrderController extends Controller
{
    public function __construct(private AdminOrderService $adminOrderService) {}

    public function index(ListAdminOrdersRequest $request): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Daftar pesanan berhasil diambil')
            ->detail('Daftar pesanan berhasil diambil')
            ->data($this->adminOrderService->list($request->validated('status')))
            ->response();
    }

    public function show(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Detail pesanan berhasil diambil')
            ->detail('Detail pesanan berhasil diambil')
            ->data($this->adminOrderService->show($uuid))
            ->response();
    }

    public function process(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Pesanan berhasil diproses')
            ->detail('Pesanan berhasil diproses')
            ->data($this->adminOrderService->process($uuid))
            ->response();
    }

    public function ship(ShipAdminOrderRequest $request, string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Pesanan berhasil dikirim')
            ->detail('Pesanan berhasil dikirim')
            ->data($this->adminOrderService->ship($uuid, $request->validated()))
            ->response();
    }

    public function complete(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Pesanan berhasil diselesaikan')
            ->detail('Pesanan berhasil diselesaikan')
            ->data($this->adminOrderService->complete($uuid))
            ->response();
    }

    public function cancel(CancelAdminOrderRequest $request, string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Pesanan berhasil dibatalkan')
            ->detail('Pesanan berhasil dibatalkan')
            ->data($this->adminOrderService->cancel($uuid, $request->validated('reason')))
            ->response();
    }
}
