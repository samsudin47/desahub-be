<?php

namespace Modules\DataManagement\Http\Requests;

use Modules\DataManagement\Http\Requests\Concerns\ValidatesMasterPenjual;

class StoreMasterPenjualRequest extends ApiFormRequest
{
    use ValidatesMasterPenjual;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->masterPenjualRules();
    }
}
