<?php

namespace Modules\IAMService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\IAMService\Models\Concerns\HasActiveRecordScopes;

class ServiceModule extends Model
{
    use HasActiveRecordScopes;

    protected $table = 'service_module';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'code',
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

    public function serviceFeatures(): HasMany
    {
        return $this->hasMany(ServiceFeature::class, 'uuid_service_module', 'uuid');
    }
}
