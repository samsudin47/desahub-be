<?php

namespace Modules\IAMService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Shared\Constants\ResponseTypeConstantsHelper;

class RoleBaseAccessControlController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->notImplemented('Get access control');
    }

    public function store(): JsonResponse
    {
        return $this->notImplemented('Store access control');
    }

    public function destroy(): JsonResponse
    {
        return $this->notImplemented('Delete access control');
    }

    public function getBasedOnMenu(): JsonResponse
    {
        return $this->notImplemented('Get access control based on menu');
    }

    public function storeBasedOnMenu(): JsonResponse
    {
        return $this->notImplemented('Store access control based on menu');
    }

    private function notImplemented(string $feature): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
            ->info("{$feature} is not implemented yet")
            ->detail('This endpoint will be available in a future release')
            ->response();
    }
}
