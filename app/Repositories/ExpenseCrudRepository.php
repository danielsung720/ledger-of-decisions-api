<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\Expense\CreateExpenseDto;
use App\DTO\Expense\ExpenseBatchDeleteQueryDto;
use App\DTO\Expense\ExpensePaginateQueryDto;
use App\DTO\Expense\UpdateExpenseDto;
use App\Enums\DatePreset;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Persistence operations for expense CRUD and batch delete use-cases.
 */
class ExpenseCrudRepository
{
    /**
     * Paginate expenses by scope and filter criteria.
     */
    public function paginate(ExpensePaginateQueryDto $queryDto): LengthAwarePaginator
    {
        $query = Expense::query()
            ->with('decision')
            ->whereIn('user_id', $queryDto->scope->userIds());

        $this->applyDateFilters($query, $queryDto);

        if ($queryDto->filters->categories !== []) {
            $query->whereIn('category', $queryDto->filters->categories);
        }

        if ($queryDto->filters->intents !== []) {
            $query->whereHas('decision', fn ($decisionQuery) => $decisionQuery->whereIn('intent', $queryDto->filters->intents));
        }

        if ($queryDto->filters->confidenceLevels !== []) {
            $query->whereHas('decision', fn ($decisionQuery) => $decisionQuery->whereIn('confidence_level', $queryDto->filters->confidenceLevels));
        }

        return $query
            ->orderBy('occurred_at', 'desc')
            ->paginate($queryDto->filters->perPage);
    }

    private function applyDateFilters(\Illuminate\Database\Eloquent\Builder $query, ExpensePaginateQueryDto $queryDto): void
    {
        if ($queryDto->filters->preset instanceof DatePreset) {
            $now = Carbon::now(config('app.timezone'));

            match ($queryDto->filters->preset) {
                DatePreset::Today => $query->whereBetween('occurred_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()]),
                DatePreset::ThisWeek => $query->whereBetween('occurred_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]),
                DatePreset::ThisMonth => $query->whereBetween('occurred_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]),
            };

            return;
        }

        if ($queryDto->filters->startDate !== null) {
            $query->where('occurred_at', '>=', Carbon::parse($queryDto->filters->startDate)->startOfDay());
        }

        if ($queryDto->filters->endDate !== null) {
            $query->where('occurred_at', '<=', Carbon::parse($queryDto->filters->endDate)->endOfDay());
        }
    }

    /**
     * Create a new expense row.
     */
    public function create(CreateExpenseDto $payload): Expense
    {
        return Expense::create($payload->toArray());
    }

    /**
     * Return one expense row with related decision.
     */
    public function show(Expense $expense): Expense
    {
        return $expense->load('decision');
    }

    /**
     * Update an expense row and reload related decision.
     */
    public function update(Expense $expense, UpdateExpenseDto $payload): Expense
    {
        $expense->update($payload->toArray());

        return $expense->load('decision');
    }

    /**
     * Delete one expense row.
     */
    public function delete(Expense $expense): void
    {
        $expense->delete();
    }

    /**
     * Batch delete expense rows scoped to current caller.
     */
    public function batchDelete(ExpenseBatchDeleteQueryDto $queryDto): int
    {
        return DB::transaction(function () use ($queryDto): int {
            return Expense::query()
                ->whereIn('user_id', $queryDto->scope->userIds())
                ->whereIn('id', $queryDto->payload->ids)
                ->delete();
        });
    }
}
