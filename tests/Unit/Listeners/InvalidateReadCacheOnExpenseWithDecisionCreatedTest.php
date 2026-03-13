<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\CacheDomainEnum;
use App\Events\ExpenseWithDecisionCreated;
use App\Listeners\InvalidateReadCacheOnExpenseWithDecisionCreated;
use App\Models\Expense;
use App\Services\ApiReadCacheService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvalidateReadCacheOnExpenseWithDecisionCreatedTest extends TestCase
{
    #[Test]
    public function ItInvalidatesStatisticsAndExpensesDomains(): void
    {
        $cacheService = $this->createMock(ApiReadCacheService::class);
        $calls = [];

        $cacheService->expects($this->exactly(2))
            ->method('invalidateDomainVersion')
            ->willReturnCallback(function (int $userId, CacheDomainEnum $domain) use (&$calls): int {
                $calls[] = [$userId, $domain->value];

                return 2;
            });

        $listener = new InvalidateReadCacheOnExpenseWithDecisionCreated($cacheService);
        $listener->handle(new ExpenseWithDecisionCreated(new Expense(['user_id' => 66])));

        $this->assertSame([[66, 'Statistics'], [66, 'Expenses']], $calls);
    }
}
