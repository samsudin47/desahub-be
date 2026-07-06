<?php

namespace Modules\IAMService\Services;

use Illuminate\Support\Facades\Auth;
use Modules\IAMService\Models\User;
use Shared\Constants\AvailableRoleConstantsHelper;

class AuthService
{
    /**
     * @return array{token: string, user: array<string, mixed>}
     */
    public function register(array $payload): array
    {
        $user = User::create([
            'uuid' => generateUuid(),
            'username' => $payload['username'],
            'email' => $payload['email'],
            'password' => $payload['password'],
            'role' => $payload['role'] ?? AvailableRoleConstantsHelper::USER,
            'is_active' => true,
            'is_deleted' => false,
            'last_activity_at' => dateNow(),
            'created_by' => 'register',
            'updated_by' => 'register',
        ]);

        $token = Auth::guard('api')->login($user);

        return [
            'token' => $token,
            'user' => $this->formatUser($user),
        ];
    }

    /**
     * @return array{token: string, user: array<string, mixed>}|null
     */
    public function login(string $username, string $password): ?array
    {
        $credentials = [
            'username' => $username,
            'password' => $password,
            'is_active' => true,
            'is_deleted' => false,
        ];

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return null;
        }

        /** @var User $user */
        $user = Auth::guard('api')->user();
        $user->update([
            'last_activity_at' => dateNow(),
            'updated_by' => 'login',
        ]);

        return [
            'token' => $token,
            'user' => $this->formatUser($user->fresh()),
        ];
    }

    public function logout(): void
    {
        Auth::guard('api')->logout();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUser(User $user): array
    {
        return [
            'uuid' => $user->uuid,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'last_activity_at' => $user->last_activity_at,
        ];
    }
}
