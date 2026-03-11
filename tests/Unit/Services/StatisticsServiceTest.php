<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Statistics\CategoryAggregateDto;
use App\DTO\Statistics\HighConfidenceIntentAggregateDto;
use App\DTO\Statistics\IntentAggregateDto;
use App\DTO\Statistics\IntentAmountAggregateDto;
use App\DTO\Statistics\StatisticsFilterDto;
use App\DTO\Statistics\StatisticsQueryDto;
use App\DTO\Statistics\SummaryTotalsDto;
use App\DTO\Statistics\TrendsImpulseComparisonDto;
use App\DTO\Statistics\TrendsStatisticsQueryDto;
use App\Enums\Category;
use App\Repositories\ExpenseRepository;
use App\Services\StatisticsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{
    #[Test]
    public function GetIntentsStatisticsShouldMapScoresAndLabelsCorrectly(): void
    {
        $repository = $this->createMock(ExpenseRepository::class);
        $repository->expects($this->once())
            ->method('getIntentStatistics')
            ->with($this->callback(function (StatisticsQueryDto $query): bool {
                return $query->scope->userIds() === [123]
                    && $query->filter->preset?->value === 'this_month';
            }))
            ->willReturn(collect([
                new IntentAggregateDto(intent: 'necessity', count: 2, avgConfidenceScore: 2.5),
                new IntentAggregateDto(intent: 'custom_intent', count: 1, avgConfidenceScore: 1.49),
            ]));

        $service = new StatisticsService($repository);
        $result = $service->getIntentsStatistics(
            StatisticsQueryDto::forUser(123, new StatisticsFilterDto(preset: \App\Enums\DatePreset::ThisMonth))
        );

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertSame('necessity', $result[0]->intent);
        $this->assertSame('必要性', $result[0]->intentLabel);
        $this->assertSame(2, $result[0]->count);
        $this->assertSame(2.5, $result[0]->avgConfidenceScore);
        $this->assertSame('high', $result[0]->avgConfidenceLevel);
        $this->assertSame('custom_intent', $result[1]->intent);
        $this->assertSame('custom_intent', $result[1]->intentLabel);
        $this->assertSame('low', $result[1]->avgConfidenceLevel);
    }

    #[Test]
    public function GetSummaryStatisticsShouldComputeRatioAndMapCategoryAndIntentLabels(): void
    {
        $repository = $this->createMock(ExpenseRepository::class);
        $repository->expects($this->once())
            ->method('getSummaryByCategory')
            ->with($this->callback(function (StatisticsQueryDto $query): bool {
                return $query->scope->userIds() === [123]
                    && $query->filter->preset?->value === 'today';
            }))
            ->willReturn(collect([
                new CategoryAggregateDto(category: Category::Food->value, totalAmount: 300, count: 2),
                new CategoryAggregateDto(category: 'custom_category', totalAmount: 80, count: 1),
            ]));
        $repository->expects($this->once())
            ->method('getSummaryByIntent')
            ->with($this->callback(function (StatisticsQueryDto $query): bool {
                return $query->scope->userIds() === [123]
                    && $query->filter->preset?->value === 'today';
            }))
            ->willReturn(collect([
                new IntentAmountAggregateDto(intent: 'impulse', totalAmount: 100, count: 1),
            ]));
        $repository->expects($this->once())
            ->method('getSummaryTotals')
            ->with($this->callback(fn (StatisticsQueryDto $query): bool => $query->scope->userIds() === [123]))
            ->willReturn(new SummaryTotalsDto(totalAmount: 380.0, totalCount: 3, impulseCount: 1));

        $service = new StatisticsService($repository);
        $result = $service->getSummaryStatistics(
            StatisticsQueryDto::forUser(123, new StatisticsFilterDto(preset: \App\Enums\DatePreset::Today))
        );

        $this->assertSame(380.0, $result->totalAmount);
        $this->assertSame(3, $result->totalCount);
        $this->assertSame(33.33, $result->impulseSpendingRatio);
        $this->assertSame('food', $result->byCategory[0]->category);
        $this->assertSame('飲食', $result->byCategory[0]->categoryLabel);
        $this->assertSame('custom_category', $result->byCategory[1]->categoryLabel);
        $this->assertSame('impulse', $result->byIntent[0]->intent);
        $this->assertSame('衝動', $result->byIntent[0]->intentLabel);
    }

    #[Test]
    public function GetSummaryStatisticsShouldReturnZeroRatioWhenTotalCountIsZero(): void
    {
        $repository = $this->createMock(ExpenseRepository::class);
        $repository->method('getSummaryByCategory')->willReturn(collect());
        $repository->method('getSummaryByIntent')->willReturn(collect());
        $repository->method('getSummaryTotals')->willReturn(new SummaryTotalsDto(totalAmount: 0.0, totalCount: 0, impulseCount: 0));

        $service = new StatisticsService($repository);
        $result = $service->getSummaryStatistics(StatisticsQueryDto::forUser(123));

        $this->assertSame(0.0, $result->impulseSpendingRatio);
    }

    #[Test]
    public function GetTrendsStatisticsShouldReturnUpTrendAndHighConfidenceIntents(): void
    {
        $repository = $this->createMock(ExpenseRepository::class);
        $repository->expects($this->once())
            ->method('getTrendsImpulseComparison')
            ->willReturn(new TrendsImpulseComparisonDto(thisWeek: 100.0, lastWeek: 50.0));
        $repository->expects($this->once())
            ->method('getTopHighConfidenceIntents')
            ->with($this->callback(fn (TrendsStatisticsQueryDto $query): bool => $query->scope->userIds() === [123]))
            ->willReturn(collect([
                new HighConfidenceIntentAggregateDto(intent: 'necessity', count: 2),
            ]));

        $service = new StatisticsService($repository);
        $result = $service->getTrendsStatistics(TrendsStatisticsQueryDto::forUser(123, Carbon::now()));

        $this->assertSame(100.0, $result->thisWeek);
        $this->assertSame(50.0, $result->lastWeek);
        $this->assertSame(100.0, $result->changePercentage);
        $this->assertSame('up', $result->trend);
        $this->assertSame('necessity', $result->highConfidenceIntents[0]->intent);
        $this->assertSame('必要性', $result->highConfidenceIntents[0]->intentLabel);
        $this->assertSame(2, $result->highConfidenceIntents[0]->count);
    }

    #[Test]
    public function GetTrendsStatisticsShouldReturnHundredPercentWhenLastWeekIsZeroAndThisWeekHasSpending(): void
    {
        $repository = $this->createMock(ExpenseRepository::class);
        $repository->expects($this->once())
            ->method('getTrendsImpulseComparison')
            ->willReturn(new TrendsImpulseComparisonDto(thisWeek: 20.0, lastWeek: 0.0));
        $repository->method('getTopHighConfidenceIntents')->willReturn(collect());

        $service = new StatisticsService($repository);
        $result = $service->getTrendsStatistics(TrendsStatisticsQueryDto::forUser(123, Carbon::now()));

        $this->assertSame(100, $result->changePercentage);
        $this->assertSame('up', $result->trend);
    }
}
