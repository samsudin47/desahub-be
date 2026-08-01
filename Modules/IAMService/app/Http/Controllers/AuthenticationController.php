<?php

namespace Modules\IAMService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\IAMService\Http\Requests\ForgotPasswordRequest;
use Modules\IAMService\Http\Requests\LoginRequest;
use Modules\IAMService\Http\Requests\RegisterRequest;
use Modules\IAMService\Http\Requests\ResetPasswordRequest;
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

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->validated('email'));

        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Password reset email sent')
            ->detail('If the email exists, a reset link has been sent')
            ->data([])
            ->response();
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $reset = $this->authService->resetPassword($request->validated());

        if (! $reset) {
            return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                ->info('Invalid or expired token')
                ->detail('Please request a new password reset link')
                ->response();
        }

        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Password reset success')
            ->detail('You can now login with your new password')
            ->data([])
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
