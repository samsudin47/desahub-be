<?php

namespace Modules\MarketplaceUmkmService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\MarketplaceUmkmService\Http\Requests\StoreCartItemRequest;
use Modules\MarketplaceUmkmService\Http\Requests\UpdateCartItemRequest;
use Modules\MarketplaceUmkmService\Services\CartService;
use Shared\Constants\ResponseTypeConstantsHelper;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function show(): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Data keranjang berhasil diambil')
            ->detail('Data keranjang berhasil diambil')
            ->data($this->cartService->getCart())
            ->response();
    }

    public function storeItem(StoreCartItemRequest $request): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Produk berhasil ditambahkan ke keranjang')
            ->detail('Produk berhasil ditambahkan ke keranjang')
            ->data($this->cartService->storeItem($request->validated()))
            ->response();
    }

    public function updateItem(UpdateCartItemRequest $request, string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Jumlah item keranjang berhasil diubah')
            ->detail('Jumlah item keranjang berhasil diubah')
            ->data($this->cartService->updateItem($uuid, $request->validated()))
            ->response();
    }

    public function plusOrderItem(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Jumlah item keranjang berhasil ditambah')
            ->detail('Jumlah item keranjang berhasil ditambah')
            ->data($this->cartService->plusOrderItem($uuid))
            ->response();
    }

    public function minusOrderItem(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Jumlah item keranjang berhasil dikurangi')
            ->detail('Jumlah item keranjang berhasil dikurangi')
            ->data($this->cartService->minusOrderItem($uuid))
            ->response();
    }

    public function destroyItem(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Item keranjang berhasil dihapus')
            ->detail('Item keranjang berhasil dihapus')
            ->data($this->cartService->destroyItem($uuid))
            ->response();
    }

    public function destroy(): JsonResponse
    {
        $this->cartService->destroy();

        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Keranjang berhasil dikosongkan')
            ->detail('Keranjang berhasil dikosongkan')
            ->data([])
            ->response();
    }
}
