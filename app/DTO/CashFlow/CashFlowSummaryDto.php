<?php

declare(strict_types=1);

namespace App\DTO\CashFlow;

/**
 * Cash flow summary response DTO.
 */
final readonly class CashFlowSummaryDto
{
    /**
     * @param  float  $totalIncome  Total monthly income amount.
     * @param  float  $totalExpense  Total monthly expense amount.
     * @param  float  $netCashFlow  Net monthly cash flow.
     * @param  float  $savingsRate  Savings rate percentage.
     */
    public function __construct(
        public float $totalIncome,
        public float $totalExpense,
        public float $netCashFlow,
        public float $savingsRate
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
            'total_income' => number_format($this->totalIncome, 2, '.', ''),
            'total_expense' => number_format($this->totalExpense, 2, '.', ''),
            'net_cash_flow' => number_format($this->netCashFlow, 2, '.', ''),
            'savings_rate' => number_format($this->savingsRate, 1, '.', ''),
        ];
    }
}
