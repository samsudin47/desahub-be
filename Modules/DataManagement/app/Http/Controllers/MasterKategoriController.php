<?php

namespace Modules\DataManagement\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\DataManagement\Http\Requests\StoreMasterKategoriRequest;
use Modules\DataManagement\Http\Requests\UpdateMasterKategoriRequest;
use Modules\DataManagement\Services\MasterKategoriService;
use Shared\Constants\ResponseTypeConstantsHelper;

class MasterKategoriController extends Controller
{
    public function __construct(private MasterKategoriService $masterKategoriService) {}

    public function index(): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Get master kategori success')
            ->detail('Master kategori retrieved successfully')
            ->data(['master_kategori' => $this->masterKategoriService->getAll()])
            ->response();
    }

    public function store(StoreMasterKategoriRequest $request): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Create master kategori success')
            ->detail('Master kategori created successfully')
            ->data($this->masterKategoriService->store($request->validated()))
            ->response();
    }

    public function show(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Get master kategori success')
            ->detail('Master kategori retrieved successfully')
            ->data($this->masterKategoriService->getByUuid($uuid))
            ->response();
    }

    public function update(UpdateMasterKategoriRequest $request, string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Update master kategori success')
            ->detail('Master kategori updated successfully')
            ->data($this->masterKategoriService->update($uuid, $request->validated()))
            ->response();
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->masterKategoriService->delete($uuid);

        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Delete master kategori success')
            ->detail('Master kategori deleted successfully')
            ->data([])
            ->response();
    }
}
