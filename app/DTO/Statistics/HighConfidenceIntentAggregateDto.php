<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

/**
 * Raw aggregate row for top high-confidence intents.
 */
final readonly class HighConfidenceIntentAggregateDto
{
    /**
     * @param  string  $intent  Intent enum value.
     * @param  int  $count  Number of matched records.
     */
    public function __construct(
        public string $intent,
        public int $count
    ) {
    }
}
