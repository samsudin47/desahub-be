<?php

namespace Modules\IAMService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\IAMService\Models\Concerns\HasActiveRecordScopes;

class UserRole extends Model
{
    use HasActiveRecordScopes;

    protected $table = 'user_role';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'uuid_user',
        'uuid_role',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uuid_user', 'uuid');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'uuid_role', 'uuid');
    }
}
