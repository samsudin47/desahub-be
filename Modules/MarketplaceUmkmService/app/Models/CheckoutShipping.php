<?php

namespace Modules\MarketplaceUmkmService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutShipping extends Model
{
    protected $table = 'checkout_shipping';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'uuid_checkout',
        'nama_penerima',
        'no_hp_penerima',
        'alamat_penerima',
        'latitude',
        'longitude',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'is_deleted',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'created_by' => 'string',
        'updated_by' => 'string',
        'deleted_by' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->where('is_deleted', false);
    }

    public function checkout(): BelongsTo
    {
        return $this->belongsTo(Checkout::class, 'uuid_checkout', 'uuid');
    }
}
