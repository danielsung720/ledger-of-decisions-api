<?php

declare(strict_types=1);

namespace App\DTO\CashFlow;

/**
 * Raw total amounts used to assemble summary response.
 */
final readonly class CashFlowSummaryAmountsDto
{
    /**
     * @param  float  $totalIncome  Total monthly income amount.
     * @param  float  $totalExpense  Total monthly expense amount.
     */
    public function __construct(
        public float $totalIncome,
        public float $totalExpense
    ) {
    }
}
