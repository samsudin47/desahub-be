<?php

namespace Modules\MarketplaceUmkmService\Http\Requests;

use Modules\MarketplaceUmkmService\Http\Requests\Concerns\ValidatesCheckout;

class UpdateCheckoutShippingRequest extends ApiFormRequest
{
    use ValidatesCheckout;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->updateCheckoutShippingRules();
    }
}
