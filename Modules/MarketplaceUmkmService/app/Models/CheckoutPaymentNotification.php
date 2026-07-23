<?php

namespace Modules\MarketplaceUmkmService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutPaymentNotification extends Model
{
    protected $table = 'checkout_payment_notification';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'uuid_checkout_payment',
        'order_id',
        'transaction_status',
        'payload',
        'signature_valid',
        'processed_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_valid' => 'boolean',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CheckoutPayment::class, 'uuid_checkout_payment', 'uuid');
    }
}
