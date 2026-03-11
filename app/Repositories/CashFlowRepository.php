<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\CashFlow\CashFlowProjectionMonthAmountsDto;
use App\DTO\CashFlow\CashFlowProjectionQueryDto;
use App\DTO\CashFlow\CashFlowSummaryAmountsDto;
use App\DTO\CashFlow\CashFlowSummaryQueryDto;
use App\Models\CashFlowItem;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Persistence queries for cash flow summary and projection calculations.
 */
class CashFlowRepository
{
    /**
     * Collect current monthly total income and expense amounts.
     */
    public function getSummaryAmounts(CashFlowSummaryQueryDto $query): CashFlowSummaryAmountsDto
    {
        $incomes = Income::query()
            ->whereIn('user_id', $query->scope->userIds())
            ->active()
            ->get();

        $items = CashFlowItem::query()
            ->whereIn('user_id', $query->scope->userIds())
            ->active()
            ->get();

        $totalIncome = $incomes->reduce(
            static fn (float $carry, Income $income): float => $carry + (float) $income->getMonthlyAmount(),
            0.0
        );

        $totalExpense = $items->reduce(
            static fn (float $carry, CashFlowItem $item): float => $carry + (float) $item->getMonthlyAmount(),
            0.0
        );

        return new CashFlowSummaryAmountsDto(
            totalIncome: $totalIncome,
            totalExpense: $totalExpense,
        );
    }

    /**
     * Collect monthly income and expense amounts for projection window.
     *
     * @return Collection<int, CashFlowProjectionMonthAmountsDto>
     */
    public function getProjectionMonthAmounts(CashFlowProjectionQueryDto $query, Carbon $startMonth): Collection
    {
        $incomes = Income::query()
            ->whereIn('user_id', $query->scope->userIds())
            ->active()
            ->get();

        $items = CashFlowItem::query()
            ->whereIn('user_id', $query->scope->userIds())
            ->active()
            ->get();

        $results = [];
        for ($i = 0; $i < $query->filters->months; $i++) {
            $currentMonth = $startMonth->copy()->addMonths($i);

            $monthIncome = $incomes->reduce(
                static fn (float $carry, Income $income): float => $carry + (float) $income->getAmountForMonth($currentMonth),
                0.0
            );

            $monthExpense = $items->reduce(
                static fn (float $carry, CashFlowItem $item): float => $carry + (float) $item->getAmountForMonth($currentMonth),
                0.0
            );

            $results[] = new CashFlowProjectionMonthAmountsDto(
                month: $currentMonth->format('Y/m'),
                income: $monthIncome,
                expense: $monthExpense,
            );
        }

        return collect($results);
    }
}
