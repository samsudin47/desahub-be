<?php

namespace Modules\MarketplaceUmkmService\Http\Middleware;

use App\Facades\ResponseStandardAPI;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Shared\Constants\ResponseTypeConstantsHelper;
use Symfony\Component\HttpFoundation\Response;

class ProtectMidtransNotificationMiddleware
{
    private const MAX_BODY_BYTES = 65536; // 64KB

    private const MAX_INVALID_PER_IP = 20;

    private const INVALID_WINDOW_SECONDS = 300; // 5 menit

    private const BLOCK_SECONDS = 900; // 15 menit

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip() ?? 'unknown';
        $blockKey = 'midtrans:block:'.$ip;

        if (Cache::has($blockKey)) {
            return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                ->info('Too many requests')
                ->detail('Temporarily blocked')
                ->response(429);
        }

        $contentLength = (int) $request->header('Content-Length', 0);
        if ($contentLength > self::MAX_BODY_BYTES) {
            $this->hitInvalid($ip);

            return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                ->info('Payload terlalu besar')
                ->detail('Payload terlalu besar')
                ->response(413);
        }

        $raw = $request->getContent();
        if (strlen($raw) > self::MAX_BODY_BYTES) {
            $this->hitInvalid($ip);

            return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                ->info('Payload terlalu besar')
                ->detail('Payload terlalu besar')
                ->response(413);
        }

        $payload = $request->all();
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'] as $field) {
            if (! isset($payload[$field]) || ! is_string($payload[$field]) || $payload[$field] === '') {
                $this->hitInvalid($ip);

                return ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Payload Midtrans tidak valid')
                    ->detail('Payload Midtrans tidak valid')
                    ->response(400);
            }
        }

        return $next($request);
    }

    private function hitInvalid(string $ip): void
    {
        $counterKey = 'midtrans:invalid:'.$ip;
        $count = (int) Cache::increment($counterKey);

        if ($count === 1) {
            Cache::put($counterKey, 1, self::INVALID_WINDOW_SECONDS);
        }

        if ($count >= self::MAX_INVALID_PER_IP) {
            Cache::put('midtrans:block:'.$ip, true, self::BLOCK_SECONDS);
        }
    }
}
