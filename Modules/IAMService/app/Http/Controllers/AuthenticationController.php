<?php

namespace Modules\IAMService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\IAMService\Http\Requests\LoginRequest;
use Modules\IAMService\Http\Requests\RegisterRequest;
use Modules\IAMService\Services\AuthService;
use Shared\Constants\ResponseTypeConstantsHelper;

class AuthenticationController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Register success')
            ->detail('Account created successfully')
            ->data($result)
            ->response();
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('username'),
            $request->validated('password'),
        );

        if ($result === null) {
            return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_UNAUTHORIZED)
                ->info('Invalid credentials')
                ->detail('Username or password is incorrect')
                ->response();
        }

        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Login success')
            ->detail('Authenticated successfully')
            ->data($result)
            ->response();
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Logout success')
            ->detail('Session ended successfully')
            ->data([])
            ->response();
    }

    public function resetPassword(): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
            ->info('Reset password is not implemented yet')
            ->detail('This endpoint will be available in a future release')
            ->response();
    }

    public function tokenValidation(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Token is valid')
            ->detail('Authenticated user retrieved successfully')
            ->data(['user' => $user])
            ->response();
    }
}
