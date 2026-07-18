<?php

namespace Modules\MarketplaceUmkmService\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesCartItem
{
    /**
     * @return array<string, mixed>
     */
    protected function storeCartItemRules(): array
    {
        return [
            'uuid_product' => [
                'required',
                'string',
                'max:50',
                Rule::exists('product', 'uuid')->where(fn ($query) => $query->where('is_deleted', false)),
            ],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function updateCartItemRules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
