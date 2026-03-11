<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

use Illuminate\Support\Collection;

/**
 * Trends statistics response DTO.
 */
final readonly class TrendsStatisticsDto
{
    /**
     * @param  float  $thisWeek  Impulse spending total for this week.
     * @param  float  $lastWeek  Impulse spending total for last week.
     * @param  float|int  $changePercentage  Week-over-week percentage change.
     * @param  string  $trend  Trend direction: up/down/stable.
     * @param  Collection<int, HighConfidenceIntentItemDto>  $highConfidenceIntents
     */
    public function __construct(
        public float $thisWeek,
        public float $lastWeek,
        public float|int $changePercentage,
        public string $trend,
        public Collection $highConfidenceIntents
    ) {
    }

    /**
     * @return array{
     *   impulse_spending: array{this_week: float, last_week: float, change_percentage: float|int, trend: string},
     *   high_confidence_intents: array<int, array{intent: string, intent_label: string, count: int}>
     * }
     */
    public function toArray(): array
    {
        return [
            'impulse_spending' => [
                'this_week' => $this->thisWeek,
                'last_week' => $this->lastWeek,
                'change_percentage' => $this->changePercentage,
                'trend' => $this->trend,
            ],
            'high_confidence_intents' => $this->highConfidenceIntents
                ->map(fn (HighConfidenceIntentItemDto $item): array => $item->toArray())
                ->values()
                ->all(),
        ];
    }
}
