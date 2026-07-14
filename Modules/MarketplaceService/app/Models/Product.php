<?php

namespace Modules\MarketplaceService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DataManagement\Models\MasterKategori;
use Modules\DataManagement\Models\MasterPenjual;

class Product extends Model
{
    protected $table = 'product';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'nama_product',
        'deskripsi',
        'harga',
        'stock',
        'gambar',
        'uuid_kategori',
        'uuid_penjual',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'is_deleted',
    ];

    protected $casts = [
        'harga' => 'integer',
        'stock' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->where('is_deleted', false);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(MasterKategori::class, 'uuid_kategori', 'uuid');
    }

    public function penjual(): BelongsTo
    {
        return $this->belongsTo(MasterPenjual::class, 'uuid_penjual', 'uuid');
    }
}
