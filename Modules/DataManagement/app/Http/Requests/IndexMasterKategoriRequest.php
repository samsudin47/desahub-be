<?php

namespace Modules\DataManagement\Http\Requests;

use Shared\Http\Requests\Concerns\ValidatesPaginatedIndex;

class IndexMasterKategoriRequest extends ApiFormRequest
{
    use ValidatesPaginatedIndex;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->paginatedIndexRules();
    }
}
