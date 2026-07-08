<?php

namespace Modules\IAMService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\IAMService\Services\RoleService;
use Shared\Constants\ResponseTypeConstantsHelper;

class UserController extends Controller
{
    public function __construct(private RoleService $roleService) {}

    public function getRoles(): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Get roles success')
            ->detail('Roles retrieved successfully')
            ->data(['roles' => $this->roleService->getRoles()])
            ->response();
    }

    public function getUsers(): JsonResponse
    {
        return $this->notImplemented('Get users');
    }

    public function getRoleUsers(string $role): JsonResponse
    {
        return $this->notImplemented('Get role users');
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
