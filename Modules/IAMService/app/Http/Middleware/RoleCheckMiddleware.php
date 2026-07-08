<?php

namespace Modules\IAMService\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\IAMService\Http\Middleware\Concerns\RespondsWithAccessDenied;
use Symfony\Component\HttpFoundation\Response;

class RoleCheckMiddleware
{
    use RespondsWithAccessDenied;

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (isHasRoleSuperadmin()) {
            return $next($request);
        }

        foreach ($roles as $role) {
            if (getUserRoles($role)) {
                return $next($request);
            }
        }

        return $this->forbidden(
            'Forbidden, you dont have permission to perform this action'
        );
    }
}
