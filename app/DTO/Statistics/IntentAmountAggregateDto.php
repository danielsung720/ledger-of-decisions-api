<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

/**
 * Raw aggregate row for intent summary amount and count.
 */
final readonly class IntentAmountAggregateDto
{
    /**
     * @param  string  $intent  Intent enum value.
     * @param  float  $totalAmount  Total amount grouped by intent.
     * @param  int  $count  Number of matched records.
     */
    public function __construct(
        public string $intent,
        public float $totalAmount,
        public int $count
    ) {
    }
}
