<?php

namespace Modules\IAMService\Services;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Modules\IAMService\Models\User;
use Shared\Constants\AvailableRoleConstantsHelper;

class AuthService
{
    public function __construct(private UserRoleService $userRoleService) {}

    /**
     * @return array{token: string, user: array<string, mixed>}
     */
    public function register(array $payload): array
    {
        $roleCode = $payload['role'] ?? AvailableRoleConstantsHelper::USER;

        $user = DB::transaction(function () use ($payload, $roleCode) {
            $user = User::create([
                'uuid' => generateUuid(),
                'username' => $payload['username'],
                'email' => $payload['email'],
                'password' => $payload['password'],
                'role' => $roleCode,
                'is_active' => true,
                'is_deleted' => false,
                'last_activity_at' => dateNow(),
                'created_by' => 'register',
                'updated_by' => 'register',
            ]);

            $this->userRoleService->assignRoleToUser($user, $roleCode, 'register');

            return $user;
        });

        $token = Auth::guard('api')->login($user);

        return [
            'token' => $token,
            'user' => $this->formatUser($user->fresh(['roles'])),
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
            'user' => $this->formatUser($user->fresh(['roles'])),
        ];
    }

    public function logout(): void
    {
        Auth::guard('api')->logout();
    }

    /**
     * Always succeeds from the caller's perspective to avoid email enumeration.
     */
    public function forgotPassword(string $email): void
    {
        $user = User::query()
            ->where('email', $email)
            ->active()
            ->notDeleted()
            ->first();

        if ($user === null) {
            return;
        }

        Password::broker()->sendResetLink([
            'email' => $email,
        ]);
    }

    /**
     * @param  array{email: string, token: string, password: string}  $payload
     */
    public function resetPassword(array $payload): bool
    {
        $user = User::query()
            ->where('email', $payload['email'])
            ->active()
            ->notDeleted()
            ->first();

        if ($user === null) {
            return false;
        }

        $status = Password::broker()->reset(
            [
                'email' => $payload['email'],
                'password' => $payload['password'],
                'password_confirmation' => $payload['password'],
                'token' => $payload['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'updated_by' => 'reset-password',
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUser(User $user): array
    {
        $roles = $user->roles->pluck('role')->values()->all();

        return [
            'uuid' => $user->uuid,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'roles' => $roles,
            'is_active' => $user->is_active,
            'last_activity_at' => $user->last_activity_at,
        ];
    }
}
