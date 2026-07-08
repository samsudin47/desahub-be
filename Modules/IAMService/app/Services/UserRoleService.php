<?php

namespace Modules\IAMService\Services;

use Modules\IAMService\Models\Role;
use Modules\IAMService\Models\User;
use Modules\IAMService\Models\UserRole;

class UserRoleService
{
    public function assignRoleToUser(User $user, string $roleCode, string $actor = 'system'): UserRole
    {
        $role = Role::query()
            ->active()
            ->notDeleted()
            ->where('role', $roleCode)
            ->firstOrFail();

        $existing = UserRole::query()
            ->where('uuid_user', $user->uuid)
            ->where('uuid_role', $role->uuid)
            ->first();

        return UserRole::query()->updateOrCreate(
            [
                'uuid_user' => $user->uuid,
                'uuid_role' => $role->uuid,
            ],
            [
                'uuid' => $existing?->uuid ?? generateUuid(),
                'is_active' => true,
                'is_deleted' => false,
                'created_by' => $existing?->created_by ?? $actor,
                'updated_by' => $actor,
            ]
        );
    }

    public function syncUserRoleFromLegacyColumn(User $user, string $actor = 'sync'): ?UserRole
    {
        if ($user->role === null || $user->role === '') {
            return null;
        }

        $roleExists = Role::query()
            ->active()
            ->notDeleted()
            ->where('role', $user->role)
            ->exists();

        if (! $roleExists) {
            return null;
        }

        return $this->assignRoleToUser($user, $user->role, $actor);
    }

    public function backfillAllUsers(string $actor = 'sync-seeder'): int
    {
        $syncedCount = 0;

        User::query()
            ->active()
            ->notDeleted()
            ->each(function (User $user) use ($actor, &$syncedCount): void {
                $userRole = $this->syncUserRoleFromLegacyColumn($user, $actor);

                if ($userRole !== null) {
                    $syncedCount++;
                }
            });

        return $syncedCount;
    }
}
