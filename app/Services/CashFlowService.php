<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CashFlow\CashFlowProjectionItemDto;
use App\DTO\CashFlow\CashFlowProjectionQueryDto;
use App\DTO\CashFlow\CashFlowSummaryDto;
use App\DTO\CashFlow\CashFlowSummaryQueryDto;
use App\Enums\CacheDomainEnum;
use App\Enums\CacheEndpointEnum;
use App\Repositories\CashFlowRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Application service for cash flow summary and projection use-cases.
 */
class CashFlowService
{
    public function __construct(
        private readonly CashFlowRepository $cashFlowRepository,
        private readonly ?ApiReadCacheService $apiReadCacheService = null
    ) {
    }

    /**
     * Compute aggregate monthly cash flow summary.
     */
    public function getSummary(CashFlowSummaryQueryDto $query): CashFlowSummaryDto
    {
        $cacheService = $this->readCacheService();

        return $cacheService->remember(
            domain: CacheDomainEnum::CashFlow,
            endpoint: CacheEndpointEnum::CashFlowSummary,
            userId: $query->scope->userIds()[0],
            query: [],
            ttlSeconds: $cacheService->ttlSeconds(CacheDomainEnum::CashFlow, CacheEndpointEnum::CashFlowSummary),
            resolver: fn (): CashFlowSummaryDto => $this->buildSummary($query),
        );
    }

    /**
     * Build month-by-month cash flow projection from current month.
     *
     * @return Collection<int, CashFlowProjectionItemDto>
     */
    public function getProjection(CashFlowProjectionQueryDto $query): Collection
    {
        $cacheService = $this->readCacheService();

        /** @var Collection<int, CashFlowProjectionItemDto> $result */
        $result = $cacheService->remember(
            domain: CacheDomainEnum::CashFlow,
            endpoint: CacheEndpointEnum::CashFlowProjection,
            userId: $query->scope->userIds()[0],
            query: ['months' => $query->filters->months],
            ttlSeconds: $cacheService->ttlSeconds(CacheDomainEnum::CashFlow, CacheEndpointEnum::CashFlowProjection),
            resolver: fn (): Collection => $this->buildProjection($query),
        );

        return $result;
    }

    /**
     * Compute aggregate monthly cash flow summary.
     */
    private function buildSummary(CashFlowSummaryQueryDto $query): CashFlowSummaryDto
    {
        $amounts = $this->cashFlowRepository->getSummaryAmounts($query);
        $netCashFlow = $amounts->totalIncome - $amounts->totalExpense;
        $savingsRate = $amounts->totalIncome > 0
            ? ($netCashFlow / $amounts->totalIncome) * 100
            : 0.0;

        return new CashFlowSummaryDto(
            totalIncome: $amounts->totalIncome,
            totalExpense: $amounts->totalExpense,
            netCashFlow: $netCashFlow,
            savingsRate: $savingsRate,
        );
    }

    /**
     * Build month-by-month cash flow projection from current month.
     *
     * @return Collection<int, CashFlowProjectionItemDto>
     */
    private function buildProjection(CashFlowProjectionQueryDto $query): Collection
    {
        $startMonth = Carbon::now()->startOfMonth();
        $monthAmounts = $this->cashFlowRepository->getProjectionMonthAmounts($query, $startMonth);

        $cumulativeBalance = 0.0;
        $result = [];

        foreach ($monthAmounts as $monthAmount) {
            $net = $monthAmount->income - $monthAmount->expense;
            $cumulativeBalance += $net;

            $result[] = new CashFlowProjectionItemDto(
                month: $monthAmount->month,
                income: $monthAmount->income,
                expense: $monthAmount->expense,
                net: $net,
                cumulativeBalance: $cumulativeBalance,
            );
        }

        return collect($result);
    }

    private function readCacheService(): ApiReadCacheService
    {
        return $this->apiReadCacheService ?? app(ApiReadCacheService::class);
    }
}
