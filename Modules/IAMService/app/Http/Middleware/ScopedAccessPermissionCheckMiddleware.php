<?php

namespace Modules\IAMService\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\IAMService\Http\Middleware\Concerns\RespondsWithAccessDenied;
use Symfony\Component\HttpFoundation\Response;

class ScopedAccessPermissionCheckMiddleware
{
    use RespondsWithAccessDenied;

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string $module,
        string $feature,
        string $access
    ): Response {
        if (isHasRoleSuperadmin()) {
            return $next($request);
        }

        $grantedModules = getUserAuthenticatedModulePermission();
        if (! in_array($module, $grantedModules, true)) {
            return $this->forbidden(
                'Forbidden, you dont have access to this module'
            );
        }

        $grantedFeatures = getUserAuthenticatedFeaturePermission();
        if (! in_array($feature, $grantedFeatures, true)) {
            return $this->forbidden(
                'Forbidden, you dont have access to this feature'
            );
        }

        $grantedAccess = getUserAuthenticatedAccessPermission();
        $featureAccess = $grantedAccess[$feature] ?? [];

        if (! in_array($access, $featureAccess, true)) {
            return $this->forbidden(
                'Forbidden, you dont have permission to perform this action'
            );
        }

        return $next($request);
    }
}
