<?php

namespace Modules\IAMService\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\IAMService\Http\Middleware\Concerns\RespondsWithAccessDenied;
use Symfony\Component\HttpFoundation\Response;

class FeatureCheckMiddleware
{
    use RespondsWithAccessDenied;

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (isHasRoleSuperadmin()) {
            return $next($request);
        }

        $grantedFeatures = getUserAuthenticatedFeaturePermission();

        if (in_array($feature, $grantedFeatures, true)) {
            return $next($request);
        }

        return $this->forbidden(
            'Forbidden, you dont have access to this feature'
        );
    }
}
