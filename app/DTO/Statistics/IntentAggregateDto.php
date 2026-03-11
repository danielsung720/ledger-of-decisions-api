<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

/**
 * Raw aggregate row for intent distribution with average confidence.
 */
final readonly class IntentAggregateDto
{
    /**
     * @param  string  $intent  Intent enum value.
     * @param  int  $count  Number of matched records.
     * @param  float  $avgConfidenceScore  Average confidence score for intent.
     */
    public function __construct(
        public string $intent,
        public int $count,
        public float $avgConfidenceScore
    ) {
    }
}
