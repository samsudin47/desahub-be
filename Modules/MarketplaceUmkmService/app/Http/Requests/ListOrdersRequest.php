<?php

namespace Modules\MarketplaceUmkmService\Http\Requests;

class ListOrdersRequest extends ApiFormRequest
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
                'in:all,draft,pending,paid,failed,expired,cancelled',
            ],
        ];
    }
}
