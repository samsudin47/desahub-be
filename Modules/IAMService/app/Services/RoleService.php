<?php

namespace Modules\IAMService\Services;

use Modules\IAMService\Models\Role;

class RoleService
{
    /**
     * @return list<array{uuid: string, role: string, name: string, description: string|null, is_system: bool}>
     */
    public function getRoles(): array
    {
        return Role::query()
            ->active()
            ->notDeleted()
            ->orderBy('role')
            ->get(['uuid', 'role', 'name', 'description', 'is_system'])
            ->map(fn (Role $role) => [
                'uuid' => $role->uuid,
                'role' => $role->role,
                'name' => $role->name,
                'description' => $role->description,
                'is_system' => $role->is_system,
            ])
            ->values()
            ->all();
    }
}
