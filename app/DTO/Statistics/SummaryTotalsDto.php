<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

/**
 * Raw total metrics used to assemble summary statistics response.
 */
final readonly class SummaryTotalsDto
{
    /**
     * @param  float  $totalAmount  Total amount in selected range.
     * @param  int  $totalCount  Total expense count in selected range.
     * @param  int  $impulseCount  Number of impulse expenses.
     */
    public function __construct(
        public float $totalAmount,
        public int $totalCount,
        public int $impulseCount
    ) {
    }
}
