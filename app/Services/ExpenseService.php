<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Expense\CreateExpenseDto;
use App\DTO\Expense\ExpenseBatchDeleteQueryDto;
use App\DTO\Expense\ExpensePaginateQueryDto;
use App\DTO\Expense\UpdateExpenseDto;
use App\Models\Expense;
use App\Repositories\ExpenseCrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Application service for expense use-cases.
 */
class ExpenseService
{
    public function __construct(
        private readonly ExpenseCrudRepository $expenseRepository
    ) {
    }

    public function paginate(ExpensePaginateQueryDto $query): LengthAwarePaginator
    {
        return $this->expenseRepository->paginate($query);
    }

    /**
     * Create a new expense.
     */
    public function create(CreateExpenseDto $payload): Expense
    {
        return $this->expenseRepository->create($payload);
    }

    /**
     * Retrieve one expense with related decision.
     */
    public function show(Expense $expense): Expense
    {
        return $this->expenseRepository->show($expense);
    }

    /**
     * Update one expense.
     */
    public function update(Expense $expense, UpdateExpenseDto $payload): Expense
    {
        return $this->expenseRepository->update($expense, $payload);
    }

    /**
     * Delete one expense.
     */
    public function delete(Expense $expense): void
    {
        $this->expenseRepository->delete($expense);
    }

    /**
     * Batch delete expenses constrained by access scope.
     */
    public function batchDelete(ExpenseBatchDeleteQueryDto $query): int
    {
        return $this->expenseRepository->batchDelete($query);
    }
}
