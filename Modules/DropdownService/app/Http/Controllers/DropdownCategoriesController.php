<?php

namespace Modules\DropdownService\Http\Controllers;

use App\Facades\ResponseStandardAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\DropdownService\Services\DropdownCategoriesService;
use Shared\Constants\ResponseTypeConstantsHelper;

class DropdownCategoriesController extends Controller
{
    public function __construct(private DropdownCategoriesService $dropdownCategoriesService) {}

    public function index(): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_SUCCESS)
            ->info('Get dropdown kategori success')
            ->detail('Dropdown kategori retrieved successfully')
            ->data(['kategori' => $this->dropdownCategoriesService->getAll()])
            ->response();
    }
}
