<?php

namespace Modules\MarketplaceUmkmService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckoutPayment extends Model
{
    protected $table = 'checkout_payment';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'uuid_checkout',
        'order_id',
        'snap_token',
        'gross_amount',
        'payment_type',
        'bank',
        'va_number',
        'bill_key',
        'biller_code',
        'transaction_id',
        'transaction_status',
        'fraud_status',
        'status',
        'expired_at',
        'paid_at',
        'raw_response',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'is_deleted',
    ];

    protected $casts = [
        'gross_amount' => 'integer',
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
        'raw_response' => 'array',
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

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function checkout(): BelongsTo
    {
        return $this->belongsTo(Checkout::class, 'uuid_checkout', 'uuid');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(CheckoutPaymentNotification::class, 'uuid_checkout_payment', 'uuid');
    }
}
