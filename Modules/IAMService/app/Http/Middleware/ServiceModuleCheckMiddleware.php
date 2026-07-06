<?php

namespace Modules\IAMService\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\IAMService\Http\Middleware\Concerns\RespondsWithAccessDenied;
use Symfony\Component\HttpFoundation\Response;

class ServiceModuleCheckMiddleware
{
    use RespondsWithAccessDenied;

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (isHasRoleSuperadmin()) {
            return $next($request);
        }

        $grantedModules = getUserAuthenticatedModulePermission();

        if (in_array($module, $grantedModules, true)) {
            return $next($request);
        }

        return $this->forbidden(
            'Forbidden, you dont have access to this module'
        );
    }
}
