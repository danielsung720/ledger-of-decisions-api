<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

/**
 * Raw impulse amount comparison between this week and last week.
 */
final readonly class TrendsImpulseComparisonDto
{
    /**
     * @param  float  $thisWeek  Impulse spending total for this week.
     * @param  float  $lastWeek  Impulse spending total for last week.
     */
    public function __construct(
        public float $thisWeek,
        public float $lastWeek
    ) {
    }
}
