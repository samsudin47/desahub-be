<?php

namespace Modules\MarketplaceService\Http\Requests;

use App\Facades\ResponseStandardAPI;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Shared\Constants\ResponseTypeConstantsHelper;

abstract class ApiFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_VALIDATION)
                ->message('Validation failed')
                ->error('Invalid request data')
                ->validationFailed($validator->errors())
                ->response()
        );
    }
}
