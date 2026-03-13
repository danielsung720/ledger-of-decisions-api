<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Enums\CacheDomainEnum;
use App\Enums\CacheEndpointEnum;
use App\Support\RedisHelper;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RedisHelperTest extends TestCase
{
    #[Test]
    public function BuildReadCacheKeysUseLedgerApiNamespaceAndPascalCaseSegments(): void
    {
        $helper = app(RedisHelper::class);

        $key = $helper->buildReadCacheKey(
            CacheDomainEnum::Statistics,
            CacheEndpointEnum::Summary,
            1,
            3,
            'abc123'
        );

        $versionKey = $helper->buildReadCacheVersionKey(CacheDomainEnum::Statistics, 1);

        $this->assertSame(
            'ledger-api:ReadCache:Statistics:Summary:User:1:Version:3:Query:abc123',
            $key
        );
        $this->assertSame('ledger-api:ReadCache:Statistics:Version:User:1', $versionKey);
    }

    #[Test]
    public function UserPreferencesLegacyKeyKeepsOriginalPatternForMigration(): void
    {
        config(['app.name' => 'Ledger of Decisions', 'app.env' => 'production']);

        $helper = app(RedisHelper::class);

        $this->assertSame(
            'ledger-of-decisions:production:user_preferences:1',
            $helper->buildLegacyUserPreferencesKey(1)
        );
    }

    #[Test]
    public function IncrementReturnsIncreasedValue(): void
    {
        $originalStore = config('cache.default');
        config(['cache.default' => 'array']);
        Cache::store('array')->clear();

        $helper = app(RedisHelper::class);

        try {
            $this->assertSame(1, $helper->increment('ledger-api:ReadCache:Statistics:Version:User:88'));
            $this->assertSame(2, $helper->increment('ledger-api:ReadCache:Statistics:Version:User:88'));
        } finally {
            Cache::store('array')->clear();
            config(['cache.default' => $originalStore]);
        }
    }

    #[Test]
    public function ReadCacheTtlIsCentrallyManaged(): void
    {
        $helper = app(RedisHelper::class);

        $this->assertSame(60, $helper->getReadCacheTtl(CacheDomainEnum::Statistics, CacheEndpointEnum::Summary));
        $this->assertSame(120, $helper->getReadCacheTtl(CacheDomainEnum::Statistics, CacheEndpointEnum::Trends));
        $this->assertSame(90, $helper->getReadCacheTtl(CacheDomainEnum::CashFlow, CacheEndpointEnum::CashFlowSummary));
        $this->assertSame(180, $helper->getReadCacheTtl(CacheDomainEnum::CashFlow, CacheEndpointEnum::CashFlowProjection));
        $this->assertSame(45, $helper->getReadCacheTtl(CacheDomainEnum::Expenses, CacheEndpointEnum::Index));
    }
}
