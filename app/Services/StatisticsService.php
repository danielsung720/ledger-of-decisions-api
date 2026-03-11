<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Statistics\CategorySummaryItemDto;
use App\DTO\Statistics\HighConfidenceIntentItemDto;
use App\DTO\Statistics\IntentStatisticsItemDto;
use App\DTO\Statistics\IntentSummaryItemDto;
use App\DTO\Statistics\StatisticsQueryDto;
use App\DTO\Statistics\SummaryStatisticsDto;
use App\DTO\Statistics\TrendsStatisticsDto;
use App\DTO\Statistics\TrendsStatisticsQueryDto;
use App\Enums\Category;
use App\Enums\Intent;
use App\Repositories\ExpenseRepository;
use Illuminate\Support\Collection;

/**
 * Application service for statistics use-cases backed by expense aggregates.
 */
class StatisticsService
{
    private const CONFIDENCE_HIGH_THRESHOLD = 2.5;
    private const CONFIDENCE_MEDIUM_THRESHOLD = 1.5;
    private const CONFIDENCE_LEVEL_HIGH = 'high';
    private const CONFIDENCE_LEVEL_MEDIUM = 'medium';
    private const CONFIDENCE_LEVEL_LOW = 'low';

    public function __construct(
        private readonly ExpenseRepository $expenseRepository
    ) {
    }

    /**
     * Build intent distribution with confidence metrics.
     *
     * @return Collection<int, IntentStatisticsItemDto>
     */
    public function getIntentsStatistics(StatisticsQueryDto $query): Collection
    {
        $stats = $this->expenseRepository->getIntentStatistics($query);

        return $stats->map(function ($item): IntentStatisticsItemDto {
            $intent = Intent::tryFrom($item->intent);

            return new IntentStatisticsItemDto(
                intent: $item->intent,
                intentLabel: $intent?->label() ?? $item->intent,
                count: $item->count,
                avgConfidenceScore: round($item->avgConfidenceScore, 2),
                avgConfidenceLevel: $this->scoreToConfidenceLevel($item->avgConfidenceScore),
            );
        });
    }

    /**
     * Build summary metrics grouped by category and intent.
     */
    public function getSummaryStatistics(StatisticsQueryDto $query): SummaryStatisticsDto
    {
        $byCategory = $this->expenseRepository
            ->getSummaryByCategory($query)
            ->map(static function ($item): CategorySummaryItemDto {
                $category = Category::tryFrom($item->category);

                return new CategorySummaryItemDto(
                    category: $item->category,
                    categoryLabel: $category?->label() ?? $item->category,
                    totalAmount: $item->totalAmount,
                    count: $item->count,
                );
            });

        $byIntent = $this->expenseRepository
            ->getSummaryByIntent($query)
            ->map(static function ($item): IntentSummaryItemDto {
                $intent = Intent::tryFrom($item->intent);

                return new IntentSummaryItemDto(
                    intent: $item->intent,
                    intentLabel: $intent?->label() ?? $item->intent,
                    totalAmount: $item->totalAmount,
                    count: $item->count,
                );
            });

        $totals = $this->expenseRepository->getSummaryTotals($query);
        $impulseRatio = $totals->totalCount > 0
            ? round($totals->impulseCount / $totals->totalCount * 100, 2)
            : 0.0;

        return new SummaryStatisticsDto(
            totalAmount: $totals->totalAmount,
            totalCount: $totals->totalCount,
            byCategory: $byCategory,
            byIntent: $byIntent,
            impulseSpendingRatio: $impulseRatio,
        );
    }

    /**
     * Build week-over-week impulse trend and top high-confidence intents.
     */
    public function getTrendsStatistics(TrendsStatisticsQueryDto $query): TrendsStatisticsDto
    {
        $impulseComparison = $this->expenseRepository->getTrendsImpulseComparison($query);
        $thisWeekImpulse = $impulseComparison->thisWeek;
        $lastWeekImpulse = $impulseComparison->lastWeek;

        $impulseChange = $lastWeekImpulse > 0
            ? round(($thisWeekImpulse - $lastWeekImpulse) / $lastWeekImpulse * 100, 2)
            : ($thisWeekImpulse > 0 ? 100 : 0);

        $highConfidenceIntents = $this->expenseRepository
            ->getTopHighConfidenceIntents($query)
            ->map(static function ($item): HighConfidenceIntentItemDto {
                $intent = Intent::tryFrom($item->intent);

                return new HighConfidenceIntentItemDto(
                    intent: $item->intent,
                    intentLabel: $intent?->label() ?? $item->intent,
                    count: $item->count,
                );
            });

        return new TrendsStatisticsDto(
            thisWeek: $thisWeekImpulse,
            lastWeek: $lastWeekImpulse,
            changePercentage: $impulseChange,
            trend: $impulseChange > 0 ? 'up' : ($impulseChange < 0 ? 'down' : 'stable'),
            highConfidenceIntents: $highConfidenceIntents,
        );
    }

    /**
     * Map average confidence score to configured level labels.
     */
    private function scoreToConfidenceLevel(float $score): string
    {
        return match (true) {
            $score >= self::CONFIDENCE_HIGH_THRESHOLD => self::CONFIDENCE_LEVEL_HIGH,
            $score >= self::CONFIDENCE_MEDIUM_THRESHOLD => self::CONFIDENCE_LEVEL_MEDIUM,
            default => self::CONFIDENCE_LEVEL_LOW,
        };
    }
}
