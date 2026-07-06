<?php

namespace Modules\IAMService\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\IAMService\Http\Middleware\Concerns\RespondsWithAccessDenied;
use Symfony\Component\HttpFoundation\Response;

class AccessPermissionCheckMiddleware
{
    use RespondsWithAccessDenied;

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $feature, string $access): Response
    {
        if (isHasRoleSuperadmin()) {
            return $next($request);
        }

        $grantedAccess = getUserAuthenticatedAccessPermission();
        $featureAccess = $grantedAccess[$feature] ?? [];

        if (in_array($access, $featureAccess, true)) {
            return $next($request);
        }

        return $this->forbidden(
            'Forbidden, you dont have permission to perform this action'
        );
    }
}
