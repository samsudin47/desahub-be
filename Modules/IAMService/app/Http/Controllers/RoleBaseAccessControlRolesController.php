<?php

namespace Modules\IAMService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\IAMService\Services\RoleService;
use Shared\Constants\ResponseTypeConstantsHelper;

class RoleBaseAccessControlRolesController extends Controller
{
    public function __construct(private RoleService $roleService) {}

    public function index(): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Get roles success')
            ->detail('Roles retrieved successfully')
            ->data(['roles' => $this->roleService->getRoles()])
            ->response();
    }
}
