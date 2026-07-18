<?php

namespace Modules\MarketplaceUmkmService\Http\Requests;

use Modules\MarketplaceUmkmService\Http\Requests\Concerns\ValidatesCartItem;

class UpdateCartItemRequest extends ApiFormRequest
{
    use ValidatesCartItem;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->updateCartItemRules();
    }
}
