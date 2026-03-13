<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\CacheDomainEnum;
use App\Events\CashFlowItemCreated;
use App\Events\IncomeUpdated;
use App\Listeners\InvalidateCashFlowReadCache;
use App\Models\CashFlowItem;
use App\Models\Income;
use App\Services\ApiReadCacheService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvalidateCashFlowReadCacheTest extends TestCase
{
    #[Test]
    public function ItInvalidatesCashflowDomainForIncomeEvents(): void
    {
        $cacheService = $this->createMock(ApiReadCacheService::class);
        $cacheService->expects($this->once())
            ->method('invalidateDomainVersion')
            ->with(12, CacheDomainEnum::CashFlow)
            ->willReturn(2);

        $listener = new InvalidateCashFlowReadCache($cacheService);
        $listener->handle(new IncomeUpdated(new Income(['user_id' => 12])));
    }

    #[Test]
    public function ItInvalidatesCashflowDomainForCashflowItemEvents(): void
    {
        $cacheService = $this->createMock(ApiReadCacheService::class);
        $cacheService->expects($this->once())
            ->method('invalidateDomainVersion')
            ->with(21, CacheDomainEnum::CashFlow)
            ->willReturn(2);

        $listener = new InvalidateCashFlowReadCache($cacheService);
        $listener->handle(new CashFlowItemCreated(new CashFlowItem(['user_id' => 21])));
    }
}
