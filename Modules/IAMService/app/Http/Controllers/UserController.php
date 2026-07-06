<?php

namespace Modules\IAMService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Shared\Constants\ResponseTypeConstantsHelper;

class UserController extends Controller
{
    public function getUsers(): JsonResponse
    {
        return $this->notImplemented('Get users');
    }

    public function getUserViewLog(string $uuidGetUser): JsonResponse
    {
        return $this->notImplemented('Get user view log');
    }

    public function storeUser(): JsonResponse
    {
        return $this->notImplemented('Store user');
    }

    public function updateUserStatus(): JsonResponse
    {
        return $this->notImplemented('Update user status');
    }

    public function updateUserRoles(): JsonResponse
    {
        return $this->notImplemented('Update user roles');
    }

    private function notImplemented(string $feature): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
            ->info("{$feature} is not implemented yet")
            ->detail('This endpoint will be available in a future release')
            ->response();
    }
}
