<?php

namespace Modules\IAMService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\IAMService\Models\Concerns\HasActiveRecordScopes;

class ServiceFeature extends Model
{
    use HasActiveRecordScopes;

    protected $table = 'service_feature';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'uuid_service_module',
        'service_module',
        'service_feature_name',
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

    public function serviceModule(): BelongsTo
    {
        return $this->belongsTo(ServiceModule::class, 'uuid_service_module', 'uuid');
    }

    public function rolePermissionServiceFeatures(): HasMany
    {
        return $this->hasMany(RolePermissionServiceFeature::class, 'uuid_service_feature', 'uuid');
    }
}
