<?php

namespace Modules\MarketplaceUmkmService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\IAMService\Models\User;

class Checkout extends Model
{
    protected $table = 'checkout';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'uuid_user',
        'uuid_cart',
        'total_items',
        'total_price',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'is_deleted',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'total_price' => 'decimal:2',
        'status' => 'string',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uuid_user', 'uuid');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'uuid_cart', 'uuid');
    }

    public function checkoutItems(): HasMany
    {
        return $this->hasMany(CheckoutItem::class, 'uuid_checkout', 'uuid');
    }
}
