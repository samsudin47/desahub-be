<?php

namespace Modules\MarketplaceService\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesProduct
{
    /**
     * @return array<string, mixed>
     */
    protected function productRules(bool $isUpdate = false): array
    {
        return [
            'nama_product' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:1000'],
            'harga' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'uuid_kategori' => [
                'required',
                'string',
                'max:50',
                Rule::exists('master_kategori', 'uuid')->where(fn ($query) => $query->where('is_deleted', false)),
            ],
            'uuid_penjual' => [
                'required',
                'string',
                'max:50',
                Rule::exists('master_penjual', 'uuid')->where(fn ($query) => $query->where('is_deleted', false)),
            ],
            'gambar' => [
                $isUpdate ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }
}
