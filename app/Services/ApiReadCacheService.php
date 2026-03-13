<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CacheDomainEnum;
use App\Enums\CacheEndpointEnum;
use App\Support\QueryNormalizer;
use App\Support\RedisHelper;
use Illuminate\Support\Facades\Log;

class ApiReadCacheService
{
    public function __construct(
        private readonly RedisHelper $redisHelper,
        private readonly QueryNormalizer $queryNormalizer
    ) {
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function buildKey(
        CacheDomainEnum $domain,
        CacheEndpointEnum $endpoint,
        int $userId,
        array $query
    ): string {
        $version = $this->getDomainVersion($domain, $userId);
        $normalizedQuery = $this->queryNormalizer->normalize($query);
        $queryHash = $this->queryNormalizer->hash($normalizedQuery);

        return $this->redisHelper->buildReadCacheKey($domain, $endpoint, $userId, $version, $queryHash);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function remember(
        CacheDomainEnum $domain,
        CacheEndpointEnum $endpoint,
        int $userId,
        array $query,
        int $ttlSeconds,
        callable $resolver
    ): mixed {
        $key = $this->buildKey($domain, $endpoint, $userId, $query);

        try {
            $cached = $this->redisHelper->get($key);

            if ($cached !== null) {
                Log::debug('Read cache hit', [
                    'domain' => $domain->value,
                    'endpoint' => $endpoint->value,
                    'user_id' => $userId,
                    'key' => $key,
                ]);

                return $cached;
            }

            Log::debug('Read cache miss', [
                'domain' => $domain->value,
                'endpoint' => $endpoint->value,
                'user_id' => $userId,
                'key' => $key,
            ]);

            $result = $resolver();
            $this->redisHelper->put($key, $result, now()->addSeconds($ttlSeconds));

            return $result;
        } catch (\Throwable $e) {
            Log::warning('Read cache fallback to resolver due to cache error', [
                'domain' => $domain->value,
                'endpoint' => $endpoint->value,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return $resolver();
        }
    }

    public function invalidateDomainVersion(int $userId, CacheDomainEnum $domain): int
    {
        $versionKey = $this->redisHelper->buildReadCacheVersionKey($domain, $userId);
        $currentVersion = max(1, (int) $this->redisHelper->get($versionKey, 1));
        $nextVersion = $currentVersion + 1;
        $this->redisHelper->forever($versionKey, $nextVersion);

        return $nextVersion;
    }

    public function getDomainVersion(CacheDomainEnum $domain, int $userId): int
    {
        $versionKey = $this->redisHelper->buildReadCacheVersionKey($domain, $userId);
        $version = (int) $this->redisHelper->get($versionKey, 1);

        return max(1, $version);
    }

    public function ttlSeconds(CacheDomainEnum $domain, CacheEndpointEnum $endpoint): int
    {
        return $this->redisHelper->getReadCacheTtl($domain, $endpoint);
    }
}
