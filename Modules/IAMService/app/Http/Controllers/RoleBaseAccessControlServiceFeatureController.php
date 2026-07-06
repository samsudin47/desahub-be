<?php

namespace Modules\IAMService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Shared\Constants\ResponseTypeConstantsHelper;

class RoleBaseAccessControlServiceFeatureController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->notImplemented('Get service features');
    }

    public function getServiceAvailable(): JsonResponse
    {
        return $this->notImplemented('Get available services');
    }

    public function getFeatureAvailableByService(string $service): JsonResponse
    {
        return $this->notImplemented('Get features by service');
    }

    private function notImplemented(string $feature): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
            ->info("{$feature} is not implemented yet")
            ->detail('This endpoint will be available in a future release')
            ->response();
    }
}
