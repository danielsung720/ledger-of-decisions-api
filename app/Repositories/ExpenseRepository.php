<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\Statistics\CategoryAggregateDto;
use App\DTO\Statistics\HighConfidenceIntentAggregateDto;
use App\DTO\Statistics\IntentAggregateDto;
use App\DTO\Statistics\IntentAmountAggregateDto;
use App\DTO\Statistics\StatisticsQueryDto;
use App\DTO\Statistics\SummaryTotalsDto;
use App\DTO\Statistics\TrendsImpulseComparisonDto;
use App\DTO\Statistics\TrendsStatisticsQueryDto;
use App\Enums\Category;
use App\Enums\ConfidenceLevel;
use App\Enums\DatePreset;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExpenseRepository
{
    /**
     * @return Collection<int, IntentAggregateDto>
     */
    public function getIntentStatistics(StatisticsQueryDto $queryDto): Collection
    {
        $query = Decision::query()
            ->whereHas('expense', fn (Builder $expenseQuery) => $expenseQuery->whereIn('user_id', $queryDto->scope->userIds()));

        $this->applyStatisticsTimeFilters($query, $queryDto, 'decisions.created_at');

        return $query
            ->select('intent', DB::raw('COUNT(*) as count'))
            ->selectRaw("AVG(CASE
                WHEN confidence_level = 'high' THEN 3
                WHEN confidence_level = 'medium' THEN 2
                WHEN confidence_level = 'low' THEN 1
                ELSE 2 END) as avg_confidence_score")
            ->groupBy('intent')
            ->get()
            ->map(static fn (Decision $item): IntentAggregateDto => new IntentAggregateDto(
                intent: $item->intent->value,
                count: (int) $item->getAttribute('count'),
                avgConfidenceScore: (float) $item->getAttribute('avg_confidence_score'),
            ));
    }

    /**
     * @return Collection<int, CategoryAggregateDto>
     */
    public function getSummaryByCategory(StatisticsQueryDto $queryDto): Collection
    {
        $query = Expense::query()->whereIn('user_id', $queryDto->scope->userIds());
        $this->applyStatisticsTimeFilters($query, $queryDto, 'expenses.occurred_at');

        return $query
            ->select('category', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->get()
            ->map(static fn (Expense $item): CategoryAggregateDto => new CategoryAggregateDto(
                category: $item->category->value,
                totalAmount: (float) $item->getAttribute('total_amount'),
                count: (int) $item->getAttribute('count'),
            ));
    }

    /**
     * @return Collection<int, IntentAmountAggregateDto>
     */
    public function getSummaryByIntent(StatisticsQueryDto $queryDto): Collection
    {
        $query = DB::table('expenses')
            ->whereIn('expenses.user_id', $queryDto->scope->userIds())
            ->join('decisions', 'expenses.id', '=', 'decisions.expense_id')
            ->select('decisions.intent', DB::raw('SUM(expenses.amount) as total_amount'), DB::raw('COUNT(*) as count'));

        $this->applyStatisticsTimeFilters($query, $queryDto, 'expenses.occurred_at');

        return $query
            ->groupBy('decisions.intent')
            ->get()
            ->map(static fn (object $item): IntentAmountAggregateDto => new IntentAmountAggregateDto(
                intent: (string) $item->intent,
                totalAmount: (float) $item->total_amount,
                count: (int) $item->count
            ));
    }

    public function getSummaryTotals(StatisticsQueryDto $queryDto): SummaryTotalsDto
    {
        $query = Expense::query()
            ->whereIn('expenses.user_id', $queryDto->scope->userIds())
            ->leftJoin('decisions', 'expenses.id', '=', 'decisions.expense_id');

        $this->applyStatisticsTimeFilters($query, $queryDto, 'expenses.occurred_at');

        /** @var object|null $totals */
        $totals = $query
            ->selectRaw('COUNT(expenses.id) as total_count')
            ->selectRaw('COALESCE(SUM(expenses.amount), 0) as total_amount')
            ->selectRaw(
                'SUM(CASE WHEN decisions.intent = ? THEN 1 ELSE 0 END) as impulse_count',
                [Intent::Impulse->value]
            )
            ->first();

        return new SummaryTotalsDto(
            totalAmount: (float) ($totals->total_amount ?? 0),
            totalCount: (int) ($totals->total_count ?? 0),
            impulseCount: (int) ($totals->impulse_count ?? 0),
        );
    }

    public function getTrendsImpulseComparison(TrendsStatisticsQueryDto $queryDto): TrendsImpulseComparisonDto
    {
        /** @var object|null $totals */
        $totals = Expense::query()
            ->whereIn('expenses.user_id', $queryDto->scope->userIds())
            ->join('decisions', function ($join): void {
                $join->on('expenses.id', '=', 'decisions.expense_id')
                    ->where('decisions.intent', Intent::Impulse->value);
            })
            ->whereBetween('expenses.occurred_at', [$queryDto->lastWeekStart, $queryDto->thisWeekEnd])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN expenses.occurred_at BETWEEN ? AND ? THEN expenses.amount ELSE 0 END), 0) as this_week',
                [$queryDto->thisWeekStart, $queryDto->thisWeekEnd]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN expenses.occurred_at BETWEEN ? AND ? THEN expenses.amount ELSE 0 END), 0) as last_week',
                [$queryDto->lastWeekStart, $queryDto->lastWeekEnd]
            )
            ->first();

        return new TrendsImpulseComparisonDto(
            thisWeek: (float) ($totals->this_week ?? 0),
            lastWeek: (float) ($totals->last_week ?? 0),
        );
    }

    /**
     * @return Collection<int, HighConfidenceIntentAggregateDto>
     */
    public function getTopHighConfidenceIntents(TrendsStatisticsQueryDto $queryDto): Collection
    {
        return Decision::query()
            ->whereHas('expense', fn (Builder $expenseQuery) => $expenseQuery->whereIn('user_id', $queryDto->scope->userIds()))
            ->where('confidence_level', ConfidenceLevel::High->value)
            ->select('intent', DB::raw('COUNT(*) as count'))
            ->groupBy('intent')
            ->orderByDesc('count')
            ->limit($queryDto->highConfidenceLimit)
            ->get()
            ->map(static fn (Decision $item): HighConfidenceIntentAggregateDto => new HighConfidenceIntentAggregateDto(
                intent: $item->intent->value,
                count: (int) $item->getAttribute('count'),
            ));
    }

    private function applyStatisticsTimeFilters(
        Builder|QueryBuilder $query,
        StatisticsQueryDto $queryDto,
        string $column
    ): void {
        if ($queryDto->filter->preset instanceof DatePreset) {
            $now = Carbon::now();
            $startOfWeek = $now->copy()->startOfWeek();
            $endOfWeek = $now->copy()->endOfWeek();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            match ($queryDto->filter->preset) {
                DatePreset::Today => $query->whereDate($column, $now->toDateString()),
                DatePreset::ThisWeek => $query->whereBetween($column, [$startOfWeek, $endOfWeek]),
                DatePreset::ThisMonth => $query->whereBetween($column, [$startOfMonth, $endOfMonth]),
            };

            return;
        }

        if ($queryDto->filter->startDate !== null) {
            $query->where($column, '>=', Carbon::parse($queryDto->filter->startDate)->startOfDay());
        }

        if ($queryDto->filter->endDate !== null) {
            $query->where($column, '<=', Carbon::parse($queryDto->filter->endDate)->endOfDay());
        }
    }
}
