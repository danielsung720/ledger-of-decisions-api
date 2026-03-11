<?php

declare(strict_types=1);

namespace App\DTO\CashFlow;

/**
 * Raw monthly amounts used to assemble projection response items.
 */
final readonly class CashFlowProjectionMonthAmountsDto
{
    /**
     * @param  string  $month  Month label in Y/m format.
     * @param  float  $income  Monthly income amount.
     * @param  float  $expense  Monthly expense amount.
     */
    public function __construct(
        public string $month,
        public float $income,
        public float $expense
    ) {
    }
}
