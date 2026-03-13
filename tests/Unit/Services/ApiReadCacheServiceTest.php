<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\CacheDomainEnum;
use App\Enums\CacheEndpointEnum;
use App\Services\ApiReadCacheService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiReadCacheServiceTest extends TestCase
{
    #[Test]
    public function RememberCachesResolverResultAndReturnsCachedValueOnSecondCall(): void
    {
        $this->runWithArrayCacheStore(function (): void {
            $service = app(ApiReadCacheService::class);
            $calls = 0;

            $result1 = $service->remember(
                domain: CacheDomainEnum::Statistics,
                endpoint: CacheEndpointEnum::Summary,
                userId: 77,
                query: ['preset' => 'this_month'],
                ttlSeconds: 60,
                resolver: function () use (&$calls): array {
                    $calls++;

                    return ['value' => 'fresh'];
                }
            );

            $result2 = $service->remember(
                domain: CacheDomainEnum::Statistics,
                endpoint: CacheEndpointEnum::Summary,
                userId: 77,
                query: ['preset' => 'this_month'],
                ttlSeconds: 60,
                resolver: function () use (&$calls): array {
                    $calls++;

                    return ['value' => 'should-not-run'];
                }
            );

            $this->assertSame(['value' => 'fresh'], $result1);
            $this->assertSame(['value' => 'fresh'], $result2);
            $this->assertSame(1, $calls);
        });
    }

    #[Test]
    public function InvalidateDomainVersionBumpsVersionUsedByBuildKey(): void
    {
        $this->runWithArrayCacheStore(function (): void {
            $service = app(ApiReadCacheService::class);

            $keyV1 = $service->buildKey(
                CacheDomainEnum::CashFlow,
                CacheEndpointEnum::CashFlowSummary,
                9,
                ['month' => '2026-03']
            );

            $newVersion = $service->invalidateDomainVersion(9, CacheDomainEnum::CashFlow);
            $keyV2 = $service->buildKey(
                CacheDomainEnum::CashFlow,
                CacheEndpointEnum::CashFlowSummary,
                9,
                ['month' => '2026-03']
            );

            $this->assertSame(2, $newVersion);
            $this->assertStringContainsString(':Version:1:', $keyV1);
            $this->assertStringContainsString(':Version:2:', $keyV2);
            $this->assertNotSame($keyV1, $keyV2);
        });
    }

    private function runWithArrayCacheStore(callable $callback): void
    {
        $originalStore = config('cache.default');
        config(['cache.default' => 'array']);
        Cache::store('array')->clear();

        try {
            $callback();
        } finally {
            Cache::store('array')->clear();
            config(['cache.default' => $originalStore]);
        }
    }
}
