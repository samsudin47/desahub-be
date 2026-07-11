<?php

namespace Modules\DataManagement\Http\Requests;

use Modules\DataManagement\Http\Requests\Concerns\ValidatesMasterKategori;

class StoreMasterKategoriRequest extends ApiFormRequest
{
    use ValidatesMasterKategori;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->masterKategoriRules();
    }
}
