<?php

namespace Modules\MarketplaceUmkmService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DataManagement\Models\MasterPenjual;
use Modules\MarketplaceService\Models\Product;

class CheckoutItem extends Model
{
    protected $table = 'checkout_item';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'uuid_checkout',
        'uuid_cart_item',
        'uuid_product',
        'uuid_penjual',
        'quantity',
        'harga_satuan',
        'subtotal',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'is_deleted',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'harga_satuan' => 'integer',
        'subtotal' => 'integer',
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

    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(CartItem::class, 'uuid_cart_item', 'uuid');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'uuid_product', 'uuid');
    }

    public function penjual(): BelongsTo
    {
        return $this->belongsTo(MasterPenjual::class, 'uuid_penjual', 'uuid');
    }
}
