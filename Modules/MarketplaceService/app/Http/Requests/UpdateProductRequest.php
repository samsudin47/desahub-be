<?php

namespace Modules\MarketplaceService\Http\Requests;

use Modules\MarketplaceService\Http\Requests\Concerns\ValidatesProduct;

class UpdateProductRequest extends ApiFormRequest
{
    use ValidatesProduct;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->productRules(isUpdate: true);
    }
}
