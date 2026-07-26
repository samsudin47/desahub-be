<?php

namespace Modules\MarketplaceUmkmService\Http\Requests;

class ShipAdminOrderRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'courier' => ['required', 'string', 'max:50'],
            'tracking_number' => ['required', 'string', 'max:100'],
        ];
    }
}
