<?php

namespace Modules\DataManagement\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\DataManagement\Http\Requests\StoreMasterPenjualRequest;
use Modules\DataManagement\Http\Requests\UpdateMasterPenjualRequest;
use Modules\DataManagement\Services\MasterPenjualService;
use Shared\Constants\ResponseTypeConstantsHelper;

class MasterPenjualController extends Controller
{
    public function __construct(private MasterPenjualService $masterPenjualService) {}

    public function index(): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Get master penjual success')
            ->detail('Master penjual retrieved successfully')
            ->data(['master_penjual' => $this->masterPenjualService->getAll()])
            ->response();
    }

    public function store(StoreMasterPenjualRequest $request): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Create master penjual success')
            ->detail('Master penjual created successfully')
            ->data($this->masterPenjualService->store($request->validated()))
            ->response();
    }

    public function show(string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Get master penjual success')
            ->detail('Master penjual retrieved successfully')
            ->data($this->masterPenjualService->getByUuid($uuid))
            ->response();
    }

    public function update(UpdateMasterPenjualRequest $request, string $uuid): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Update master penjual success')
            ->detail('Master penjual updated successfully')
            ->data($this->masterPenjualService->update($uuid, $request->validated()))
            ->response();
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->masterPenjualService->delete($uuid);

        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Delete master penjual success')
            ->detail('Master penjual deleted successfully')
            ->data([])
            ->response();
    }
}
