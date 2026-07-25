<?php

namespace Modules\IAMService\Http\Middleware;

use App\Facades\ResponseStandardAPI;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Shared\Constants\ResponseTypeConstantsHelper;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        $accessToken = $request->bearerToken();
        if (! $accessToken) {
            return $this->unauthorized(
                'Unauthorized, you dont have permission to perform this action'
            );
        }

        try {
            Auth::shouldUse('api');
            $user = JWTAuth::setToken($accessToken)->authenticate();
        } catch (JWTException $exception) {
            Log::channel('single')->error('IAM Authentication | JWT validation failed: '.$exception->getMessage());

            return $this->unauthorized(
                'Unauthorized, invalid or expired token'
            );
        }

        if ($user === null) {
            return $this->unauthorized(
                'Unauthorized, user not found'
            );
        }

        if ($user->is_deleted ?? false) {
            return $this->unauthorized(
                'Unauthorized, your account is inactive'
            );
        }

        $lockedResponse = $this->resolveLockedAccount($user);
        if ($lockedResponse instanceof JsonResponse) {
            return $lockedResponse;
        }

        $activityResponse = $this->checkLatestActivity($user);
        if ($activityResponse instanceof JsonResponse) {
            return $activityResponse;
        }

        $guards = empty($guards) ? ['api'] : $guards;
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return $next($request);
            }
        }

        return $this->unauthorized(
            'Unauthorized, you dont have permission to perform this action'
        );
    }

    protected function shouldPassThrough(Request $request): bool
    {
        foreach ($this->publicRoutes() as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function publicRoutes(): array
    {
        return config('iamservice.auth.public_routes', [
            'api/v1/iam-services/health',
            'api/v1/iam-services/roles',
            'api/v1/iam-services/auth/login',
            'api/v1/iam-services/auth/register',
            'api/v1/iam-services/auth/reset-password',
            'api/v1/iam/auth/login',
            'api/v1/iam/auth/register',
            'api/v1/iam/auth/reset-password',
            'api/v1/marketplace-umkm-service/midtrans/notification',
        ]);
    }

    protected function unauthorized(string $message): JsonResponse
    {
        return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_UNAUTHORIZED)
            ->info($message)
            ->detail($message)
            ->response();
    }

    protected function resolveLockedAccount(object $user): JsonResponse|bool
    {
        if (! ($user->is_locked ?? false)) {
            return true;
        }

        if (isset($user->locked_until) && Carbon::now()->lessThan(Carbon::parse($user->locked_until))) {
            return $this->unauthorized(
                'Unauthorized, your account is locked, please contact administrator'
            );
        }

        $this->touchUserAttributes($user, [
            'is_locked' => false,
            'locked_until' => null,
            'login_attempts' => 0,
        ]);

        return true;
    }

    protected function checkLatestActivity(object $user): JsonResponse|bool
    {
        $now = Carbon::now();
        $warningMinutes = (int) config('iamservice.auth.inactivity_warning_minutes', 10);
        $expiryMinutes = (int) config('iamservice.auth.inactivity_expiry_minutes', 30);

        if (! isset($user->last_activity_at) || $user->last_activity_at === null) {
            $this->touchUserAttributes($user, ['last_activity_at' => $now]);

            return true;
        }

        $lastActivity = Carbon::parse($user->last_activity_at);
        $diffInMinutes = $lastActivity->diffInMinutes($now);

        if ($diffInMinutes >= $warningMinutes && $diffInMinutes <= $expiryMinutes) {
            $this->touchUserAttributes($user, ['last_activity_at' => $now]);

            return true;
        }

        if ($diffInMinutes > $expiryMinutes) {
            Auth::guard('api')->logout();

            $this->touchUserAttributes($user, [
                'count_logout' => ($user->count_logout ?? 0) + 1,
                'last_logout' => dateNow(),
            ]);

            return $this->unauthorized(
                'Unauthorized, your session expired, please re-login'
            );
        }

        $this->touchUserAttributes($user, ['last_activity_at' => $now]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function touchUserAttributes(object $user, array $attributes): void
    {
        if (! method_exists($user, 'isFillable')) {
            return;
        }

        $updates = [];
        foreach ($attributes as $key => $value) {
            if ($user->isFillable($key)) {
                $updates[$key] = $value;
            }
        }

        if ($updates === []) {
            return;
        }

        $user->fill($updates);
        $user->save();
    }
}
