<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use Shared\Utilities\ResponseAPIHelper;

/**
 * @method static \Shared\Utilities\ type(string $type)
 * @method static \Shared\Utilities\ info(string $info)
 * @method static \Shared\Utilities\ data(string $data)
 * @method static \Shared\Utilities\ message(string $message)
 * @method static \Shared\Utilities\ detail(string $detail)
 * @method static \Shared\Utilities\ error(string $error)
 * @method static string response()
 */
class ResponseStandardAPI extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ResponseAPIHelper::class;
    }
}
