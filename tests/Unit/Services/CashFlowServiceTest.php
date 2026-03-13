<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\CashFlow\CashFlowProjectionFiltersDto;
use App\DTO\CashFlow\CashFlowProjectionQueryDto;
use App\DTO\CashFlow\CashFlowSummaryQueryDto;
use App\Enums\CacheDomainEnum;
use App\Enums\CacheEndpointEnum;
use App\Models\CashFlowItem;
use App\Models\Income;
use App\Repositories\CashFlowRepository;
use App\Services\ApiReadCacheService;
use App\Services\CashFlowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class CashFlowServiceTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    private CashFlowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        $this->service = new CashFlowService(new CashFlowRepository);
        Carbon::setTestNow('2026-02-08');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function GetSummaryShouldReturnMonthlyTotalsAndSavingsRate(): void
    {
        Income::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 80000,
            'is_active' => true,
        ]);
        Income::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 20000,
            'is_active' => false,
        ]);

        CashFlowItem::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 30000,
            'is_active' => true,
        ]);

        $summary = $this->service->getSummary(
            CashFlowSummaryQueryDto::forUser((int) $this->user->id)
        );

        $this->assertSame('80000.00', $summary->toArray()['total_income']);
        $this->assertSame('30000.00', $summary->toArray()['total_expense']);
        $this->assertSame('50000.00', $summary->toArray()['net_cash_flow']);
        $this->assertSame('62.5', $summary->toArray()['savings_rate']);
    }

    #[Test]
    public function GetSummaryShouldReturnZeroSavingsRateWhenNoIncome(): void
    {
        CashFlowItem::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 25000,
            'is_active' => true,
        ]);

        $summary = $this->service->getSummary(
            CashFlowSummaryQueryDto::forUser((int) $this->user->id)
        );

        $this->assertSame('0.0', $summary->toArray()['savings_rate']);
    }

    #[Test]
    public function GetProjectionShouldReturnCumulativeSeries(): void
    {
        Income::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 80000,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);
        CashFlowItem::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 50000,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $projection = $this->service->getProjection(
            CashFlowProjectionQueryDto::forUser(
                (int) $this->user->id,
                new CashFlowProjectionFiltersDto(months: 3)
            )
        );

        $this->assertCount(3, $projection);
        $this->assertSame('2026/02', $projection[0]->month);
        $this->assertSame('30000.00', $projection[0]->toArray()['net']);
        $this->assertSame('90000.00', $projection[2]->toArray()['cumulative_balance']);
    }

    #[Test]
    public function GetProjectionShouldPassMonthsIntoCacheQuery(): void
    {
        $repository = $this->createMock(CashFlowRepository::class);
        $cacheService = $this->createMock(ApiReadCacheService::class);

        $cacheService->expects($this->once())
            ->method('ttlSeconds')
            ->with(CacheDomainEnum::CashFlow, CacheEndpointEnum::CashFlowProjection)
            ->willReturn(180);

        $cacheService->expects($this->once())
            ->method('remember')
            ->with(
                CacheDomainEnum::CashFlow,
                CacheEndpointEnum::CashFlowProjection,
                (int) $this->user->id,
                ['months' => 6],
                180,
                $this->isType('callable')
            )
            ->willReturn(collect());

        $service = new CashFlowService($repository, $cacheService);
        $service->getProjection(
            CashFlowProjectionQueryDto::forUser(
                (int) $this->user->id,
                new CashFlowProjectionFiltersDto(months: 6)
            )
        );
    }
}
