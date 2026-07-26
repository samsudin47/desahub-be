<?php

namespace Modules\MarketplaceUmkmService\Http\Requests;

use Illuminate\Validation\Rule;
use Shared\Constants\CheckoutStatusConstantsHelper;

class ListAdminOrdersRequest extends ApiFormRequest
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
            'status' => [
                'nullable',
                'string',
                Rule::in(CheckoutStatusConstantsHelper::listFilter()),
            ],
        ];
    }
}
