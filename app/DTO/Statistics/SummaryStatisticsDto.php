<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

use Illuminate\Support\Collection;

/**
 * Summary statistics response DTO.
 */
final readonly class SummaryStatisticsDto
{
    /**
     * @param  float  $totalAmount  Total amount in selected range.
     * @param  int  $totalCount  Total expense count in selected range.
     * @param  Collection<int, CategorySummaryItemDto>  $byCategory
     * @param  Collection<int, IntentSummaryItemDto>  $byIntent
     * @param  float  $impulseSpendingRatio  Impulse ratio in percent (0-100).
     */
    public function __construct(
        public float $totalAmount,
        public int $totalCount,
        public Collection $byCategory,
        public Collection $byIntent,
        public float $impulseSpendingRatio
    ) {
    }

    /**
     * @return array{
     *   total_amount: float,
     *   total_count: int,
     *   by_category: array<int, array{category: string, category_label: string, total_amount: float, count: int}>,
     *   by_intent: array<int, array{intent: string, intent_label: string, total_amount: float, count: int}>,
     *   impulse_spending_ratio: float
     * }
     */
    public function toArray(): array
    {
        return [
            'total_amount' => $this->totalAmount,
            'total_count' => $this->totalCount,
            'by_category' => $this->byCategory->map(fn (CategorySummaryItemDto $item): array => $item->toArray())->values()->all(),
            'by_intent' => $this->byIntent->map(fn (IntentSummaryItemDto $item): array => $item->toArray())->values()->all(),
            'impulse_spending_ratio' => $this->impulseSpendingRatio,
        ];
    }
}
