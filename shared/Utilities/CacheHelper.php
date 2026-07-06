<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Shared\Constants\RedisCacheConstantsHelper;

function generateCacheKey(string $customKey): string
{
    return $customKey;
}

function getCache(string $customKey): mixed
{
    $key = generateCacheKey($customKey);
    return Cache::get($key);
}

function storeCache(string $customKey, mixed $value, int $lifetime_in_minutes = 3): bool
{
    $key = generateCacheKey($customKey);
    return Cache::put($key, $value, now()->addMinutes($lifetime_in_minutes));
}

function removeCache(string $customKey): bool
{
    $key = generateCacheKey($customKey);
    return Cache::forget($key);
}

function removeCacheByPattern(string $pattern): int
{
    $collectedKeyToBeDelete = [
        RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_ACCESS_CONTROL => [
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_ACCESS_CONTROL,
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_ROLE,
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_PERMISSION,
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_SERVICE_FEATURE,
        ],
        RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_ROLE => [
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_ACCESS_CONTROL,
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_ROLE,
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_PERMISSION,
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_SERVICE_FEATURE,
        ],
        RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_PERMISSION => [
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_ACCESS_CONTROL,
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_ROLE,
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_PERMISSION,
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_SERVICE_FEATURE,
        ],
        RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_SERVICE_FEATURE => [
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_ACCESS_CONTROL,
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_ROLE,
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_PERMISSION,
            RedisCacheConstantsHelper::KEY_DATA_IAM_RBAC_SERVICE_FEATURE,
        ],
    ];

    try {
        $cacheToBeClear = $collectedKeyToBeDelete[$pattern];
    } catch (Exception $e) {
        $cacheToBeClear = [$pattern];
    }

    $prefix = config('cache.prefix');
    $redis = Redis::connection(config('cache.stores.redis.connection'));

    $cursor = 0;
    $totalDeleted = 0;
    foreach ($cacheToBeClear as $cacheKeyPattern) {
        $fullPattern = $prefix . $cacheKeyPattern . '*';

        do {
            [$cursor, $keys] = $redis->scan($cursor, 'match', $fullPattern, 'count', 1000);

            if (!empty($keys)) {
                $deletedCount = $redis->del($keys);
                $totalDeleted += $deletedCount;
            }
        } while ($cursor !== '0');
    }

    return $totalDeleted;
}
