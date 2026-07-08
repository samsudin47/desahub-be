<?php

namespace Modules\IAMService\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasActiveRecordScopes
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->where('is_deleted', false);
    }
}
