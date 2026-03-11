<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\RecurringExpense\CreateRecurringExpenseDto;
use App\DTO\RecurringExpense\RecurringExpensePaginateQueryDto;
use App\DTO\RecurringExpense\RecurringExpenseUpcomingQueryDto;
use App\DTO\RecurringExpense\UpdateRecurringExpenseDto;
use App\Models\RecurringExpense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Persistence operations for recurring expense use-cases.
 */
class RecurringExpenseRepository
{
    /**
     * Paginate recurring expenses by scope and filters.
     */
    public function paginate(RecurringExpensePaginateQueryDto $query): LengthAwarePaginator
    {
        $builder = RecurringExpense::query()
            ->whereIn('user_id', $query->scope->userIds())
            ->withCount('expenses');

        if ($query->filters->categories !== []) {
            $builder->whereIn('category', $query->filters->categories);
        }

        if ($query->filters->isActive !== null) {
            $builder->where('is_active', $query->filters->isActive);
        }

        if ($query->filters->frequencyTypes !== []) {
            $builder->whereIn('frequency_type', $query->filters->frequencyTypes);
        }

        return $builder
            ->orderBy('next_occurrence')
            ->paginate($query->filters->perPage);
    }

    /**
     * Create a new recurring expense row.
     */
    public function create(CreateRecurringExpenseDto $payload): RecurringExpense
    {
        return RecurringExpense::create($payload->toArray());
    }

    /**
     * Return one recurring expense row with generated-expense count.
     */
    public function show(RecurringExpense $recurringExpense): RecurringExpense
    {
        return $recurringExpense->loadCount('expenses');
    }

    /**
     * Update a recurring expense row and reload generated-expense count.
     */
    public function update(RecurringExpense $recurringExpense, UpdateRecurringExpenseDto $payload): RecurringExpense
    {
        $recurringExpense->update($payload->toArray());

        return $recurringExpense->loadCount('expenses');
    }

    /**
     * Delete one recurring expense row.
     */
    public function delete(RecurringExpense $recurringExpense): void
    {
        $recurringExpense->delete();
    }

    /**
     * @return Collection<int, RecurringExpense>
     */
    public function getUpcoming(RecurringExpenseUpcomingQueryDto $query): Collection
    {
        return RecurringExpense::query()
            ->whereIn('user_id', $query->scope->userIds())
            ->upcoming($query->days)
            ->orderBy('next_occurrence')
            ->get();
    }

    /**
     * @return Collection<int, \App\Models\Expense>
     */
    public function getHistory(RecurringExpense $recurringExpense, int $limit): Collection
    {
        /** @var Collection<int, \App\Models\Expense> */
        return $recurringExpense->expenses()
            ->with('decision')
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }
}
