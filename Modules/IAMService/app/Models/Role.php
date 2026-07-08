<?php

namespace Modules\IAMService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\IAMService\Models\Concerns\HasActiveRecordScopes;

class Role extends Model
{
    use HasActiveRecordScopes;

    protected $table = 'role';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'role',
        'name',
        'description',
        'is_system',
        'is_active',
        'is_deleted',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'is_deleted' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role', 'uuid_role', 'uuid_user', 'uuid', 'uuid')
            ->withPivot(['uuid', 'is_active', 'is_deleted', 'created_by', 'updated_by', 'deleted_by'])
            ->wherePivot('is_active', true)
            ->wherePivot('is_deleted', false);
    }

    public function rolePermissionServiceFeatures(): HasMany
    {
        return $this->hasMany(RolePermissionServiceFeature::class, 'uuid_role', 'uuid');
    }
}
