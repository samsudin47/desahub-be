<?php

namespace Modules\IAMService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\IAMService\Models\Concerns\HasActiveRecordScopes;

class RolePermissionServiceFeature extends Model
{
    use HasActiveRecordScopes;

    protected $table = 'role_permission_service_feature';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'uuid_role',
        'uuid_service_feature',
        'uuid_permission',
        'is_active',
        'is_deleted',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_deleted' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'uuid_role', 'uuid');
    }

    public function serviceFeature(): BelongsTo
    {
        return $this->belongsTo(ServiceFeature::class, 'uuid_service_feature', 'uuid');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'uuid_permission', 'uuid');
    }
}
