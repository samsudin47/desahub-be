<?php

namespace Modules\MarketplaceService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\MarketplaceService\Http\Requests\StoreProductRequest;
use Modules\MarketplaceService\Http\Requests\UpdateProductRequest;
use Modules\MarketplaceService\Services\ProductService;
use Shared\Constants\ResponseTypeConstantsHelper;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function index(): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Get product success')
            ->detail('Product retrieved successfully')
            ->data(['product' => $this->productService->getAll()])
            ->response();
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Create product success')
            ->detail('Product created successfully')
            ->data($this->productService->store($request->validated()))
            ->response();
    }

    public function show(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Get product success')
            ->detail('Product retrieved successfully')
            ->data($this->productService->getByUuid($uuid))
            ->response();
    }

    public function update(UpdateProductRequest $request, string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Update product success')
            ->detail('Product updated successfully')
            ->data($this->productService->update($uuid, $request->validated()))
            ->response();
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->productService->delete($uuid);

        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Delete product success')
            ->detail('Product deleted successfully')
            ->data([])
            ->response();
    }
}
