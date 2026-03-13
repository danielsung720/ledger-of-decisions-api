<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\CacheDomainEnum;
use App\Enums\CacheEndpointEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RedisHelper
{
    private const NAMESPACE = 'ledger-api';

    private const READ_CACHE = 'ReadCache';

    private const USER_PREFERENCES = 'UserPreferences';

    private const VERIFICATION = 'Verification';

    private const READ_CACHE_TTLS = [
        'Statistics' => [
            'Summary' => 60,
            'Trends' => 120,
        ],
        'CashFlow' => [
            'CashFlowSummary' => 90,
            'CashFlowProjection' => 180,
        ],
        'Expenses' => [
            'Index' => 45,
        ],
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::get($key, $default);
        } catch (\Throwable $e) {
            Log::warning('RedisHelper get failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return $default;
        }
    }

    public function put(string $key, mixed $value, mixed $ttl): void
    {
        try {
            Cache::put($key, $value, $ttl);
        } catch (\Throwable $e) {
            Log::warning('RedisHelper put failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function forever(string $key, mixed $value): void
    {
        try {
            Cache::forever($key, $value);
        } catch (\Throwable $e) {
            Log::warning('RedisHelper forever failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function has(string $key): bool
    {
        try {
            return Cache::has($key);
        } catch (\Throwable $e) {
            Log::warning('RedisHelper has failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function forget(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (\Throwable $e) {
            Log::warning('RedisHelper forget failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function increment(string $key, int $by = 1): int
    {
        try {
            return (int) Cache::increment($key, $by);
        } catch (\Throwable $e) {
            Log::warning('RedisHelper increment failed', [
                'key' => $key,
                'by' => $by,
                'error' => $e->getMessage(),
            ]);

            return 1;
        }
    }

    public function buildReadCacheKey(
        CacheDomainEnum $domain,
        CacheEndpointEnum $endpoint,
        int $userId,
        int $version,
        string $queryHash
    ): string {
        return $this->buildKey([
            self::READ_CACHE,
            $domain->value,
            $endpoint->value,
            'User',
            (string) $userId,
            'Version',
            (string) $version,
            'Query',
            $queryHash,
        ]);
    }

    public function buildReadCacheVersionKey(CacheDomainEnum $domain, int $userId): string
    {
        return $this->buildKey([
            self::READ_CACHE,
            $domain->value,
            'Version',
            'User',
            (string) $userId,
        ]);
    }

    public function getReadCacheTtl(CacheDomainEnum $domain, CacheEndpointEnum $endpoint): int
    {
        return self::READ_CACHE_TTLS[$domain->value][$endpoint->value]
            ?? throw new \InvalidArgumentException("TTL not configured for {$domain->value}:{$endpoint->value}");
    }

    public function buildUserPreferencesKey(int $userId): string
    {
        return $this->buildKey([self::USER_PREFERENCES, 'User', (string) $userId]);
    }

    public function buildLegacyUserPreferencesKey(int $userId): string
    {
        $app = strtolower((string) config('app.name', 'ledger-of-decisions'));
        $env = strtolower((string) config('app.env', 'local'));

        return sprintf('%s:%s:user_preferences:%d', str_replace(' ', '-', $app), $env, $userId);
    }

    public function buildVerificationKey(string $type, string $emailHash, string $suffix): string
    {
        return $this->buildKey([
            self::VERIFICATION,
            $this->normalizeSegment($type),
            $emailHash,
            $this->normalizeSegment($suffix),
        ]);
    }

    public function buildLegacyVerificationKey(string $type, string $emailHash, string $suffix): string
    {
        $app = strtolower((string) config('app.name', 'ledger-of-decisions'));
        $env = strtolower((string) config('app.env', 'local'));

        return sprintf(
            '%s:%s:verification:%s:%s:%s',
            str_replace(' ', '-', $app),
            $env,
            $type,
            $emailHash,
            $suffix
        );
    }

    /**
     * @param  array<int, string>  $segments
     */
    public function buildKey(array $segments): string
    {
        $normalized = array_map(fn (string $segment): string => $this->normalizeSegment($segment), $segments);

        return self::NAMESPACE . ':' . implode(':', $normalized);
    }

    private function normalizeSegment(string $segment): string
    {
        return trim($segment);
    }
}
