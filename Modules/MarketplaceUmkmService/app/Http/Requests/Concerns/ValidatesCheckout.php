<?php

namespace Modules\MarketplaceUmkmService\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesCheckout
{
    /**
     * @return array<string, mixed>
     */
    protected function storeCheckoutRules(): array
    {
        return [
            'cart_item_uuids' => ['required', 'array', 'min:1'],
            'cart_item_uuids.*' => [
                'required',
                'string',
                'max:50',
                'distinct',
                Rule::exists('cart_item', 'uuid')->where(function ($query) {
                    $query->where('is_deleted', false)
                        ->whereIn('uuid_cart', function ($subQuery) {
                            $subQuery->select('uuid')
                                ->from('cart')
                                ->where('uuid_user', getUserId())
                                ->where('is_deleted', false);
                        });
                }),
            ],
        ];
    }
}
