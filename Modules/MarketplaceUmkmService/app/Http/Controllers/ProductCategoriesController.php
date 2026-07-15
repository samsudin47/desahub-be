<?php

namespace Modules\MarketplaceUmkmService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\MarketplaceUmkmService\Services\ProductCategoriesService;
use Shared\Constants\ResponseTypeConstantsHelper;

class ProductCategoriesController extends Controller
{
    public function __construct(private ProductCategoriesService $productCategoriesService) {}

    public function show(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Data kategori berhasil diambil')
            ->detail('Data kategori berhasil diambil')
            ->data($this->productCategoriesService->getByUuid($uuid))
            ->response();
    }
}
