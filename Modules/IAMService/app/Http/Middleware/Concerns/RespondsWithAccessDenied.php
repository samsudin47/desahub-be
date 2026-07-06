<?php

namespace Modules\IAMService\Http\Middleware\Concerns;

use App\Facades\ResponseStandardAPI;
use Illuminate\Http\JsonResponse;
use Shared\Constants\ResponseTypeConstantsHelper;

trait RespondsWithAccessDenied
{
    protected function forbidden(string $message): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_FORBIDDEN_ACCESS)
            ->info($message)
            ->detail($message)
            ->response();
    }
}
