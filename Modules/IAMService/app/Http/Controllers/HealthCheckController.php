<?php

namespace Modules\IAMService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Shared\Constants\ResponseTypeConstantsHelper;

class HealthCheckController extends Controller
{
    public function index(): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('IAM Service is healthy')
            ->detail('Health check passed')
            ->data(['service' => 'IAMService', 'status' => 'up'])
            ->response();
    }
}
