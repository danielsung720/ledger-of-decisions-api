<?php

declare(strict_types=1);

namespace App\DTO\CashFlow;

/**
 * Projection response item for one month.
 */
final readonly class CashFlowProjectionItemDto
{
    /**
     * @param  string  $month  Month label in Y/m format.
     * @param  float  $income  Monthly income amount.
     * @param  float  $expense  Monthly expense amount.
     * @param  float  $net  Monthly net cash flow amount.
     * @param  float  $cumulativeBalance  Cumulative balance up to this month.
     */
    public function __construct(
        public string $month,
        public float $income,
        public float $expense,
        public float $net,
        public float $cumulativeBalance
    ) {
    }

    /**
     * Convert DTO to API response payload.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'month' => $this->month,
            'income' => number_format($this->income, 2, '.', ''),
            'expense' => number_format($this->expense, 2, '.', ''),
            'net' => number_format($this->net, 2, '.', ''),
            'cumulative_balance' => number_format($this->cumulativeBalance, 2, '.', ''),
        ];
    }
}
