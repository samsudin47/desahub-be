<?php

namespace Modules\IAMService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\IAMService\Models\Concerns\HasActiveRecordScopes;

class Permission extends Model
{
    use HasActiveRecordScopes;

    protected $table = 'permission';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'name',
        'label',
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

    public function rolePermissionServiceFeatures(): HasMany
    {
        return $this->hasMany(RolePermissionServiceFeature::class, 'uuid_permission', 'uuid');
    }
}
