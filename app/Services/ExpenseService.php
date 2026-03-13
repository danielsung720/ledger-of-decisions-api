<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Expense\CreateExpenseDto;
use App\DTO\Expense\ExpenseBatchDeleteQueryDto;
use App\DTO\Expense\ExpensePaginateQueryDto;
use App\DTO\Expense\UpdateExpenseDto;
use App\Enums\CacheDomainEnum;
use App\Enums\CacheEndpointEnum;
use App\Enums\DatePreset;
use App\Models\Expense;
use App\Repositories\ExpenseCrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Application service for expense use-cases.
 */
class ExpenseService
{
    public function __construct(
        private readonly ExpenseCrudRepository $expenseRepository,
        private readonly ?ApiReadCacheService $apiReadCacheService = null
    ) {
    }

    public function paginate(ExpensePaginateQueryDto $query): LengthAwarePaginator
    {
        if ($this->isDashboardRecentQuery($query)) {
            $cacheService = $this->readCacheService();

            /** @var LengthAwarePaginator $result */
            $result = $cacheService->remember(
                domain: CacheDomainEnum::Expenses,
                endpoint: CacheEndpointEnum::Index,
                userId: $query->scope->userIds()[0],
                query: [
                    'preset' => $query->filters->preset?->value,
                    'per_page' => $query->filters->perPage,
                    'page' => request()->integer('page', 1),
                    'category' => implode(',', $query->filters->categories),
                    'intent' => implode(',', $query->filters->intents),
                    'confidence_level' => implode(',', $query->filters->confidenceLevels),
                ],
                ttlSeconds: $cacheService->ttlSeconds(CacheDomainEnum::Expenses, CacheEndpointEnum::Index),
                resolver: fn (): LengthAwarePaginator => $this->expenseRepository->paginate($query),
            );

            return $result;
        }

        return $this->expenseRepository->paginate($query);
    }

    /**
     * Create a new expense.
     */
    public function create(CreateExpenseDto $payload): Expense
    {
        $expense = $this->expenseRepository->create($payload);
        $this->invalidateAfterWrite((int) $expense->user_id);

        return $expense;
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
        $updatedExpense = $this->expenseRepository->update($expense, $payload);
        $this->invalidateAfterWrite((int) $updatedExpense->user_id);

        return $updatedExpense;
    }

    /**
     * Delete one expense.
     */
    public function delete(Expense $expense): void
    {
        $this->expenseRepository->delete($expense);
        $this->invalidateAfterWrite((int) $expense->user_id);
    }

    /**
     * Batch delete expenses constrained by access scope.
     */
    public function batchDelete(ExpenseBatchDeleteQueryDto $query): int
    {
        $deletedCount = $this->expenseRepository->batchDelete($query);

        if ($deletedCount > 0) {
            $this->invalidateAfterBatchDelete($query);
        }

        return $deletedCount;
    }

    private function isDashboardRecentQuery(ExpensePaginateQueryDto $query): bool
    {
        return $query->filters->preset === DatePreset::ThisMonth
            && $query->filters->startDate === null
            && $query->filters->endDate === null
            && $query->filters->categories === []
            && $query->filters->intents === []
            && $query->filters->confidenceLevels === []
            && $query->filters->perPage === 5;
    }

    private function readCacheService(): ApiReadCacheService
    {
        return $this->apiReadCacheService ?? app(ApiReadCacheService::class);
    }

    private function invalidateAfterWrite(int $userId): void
    {
        $cacheService = $this->readCacheService();
        $cacheService->invalidateDomainVersion($userId, CacheDomainEnum::Statistics);
        $cacheService->invalidateDomainVersion($userId, CacheDomainEnum::Expenses);
    }

    private function invalidateAfterBatchDelete(ExpenseBatchDeleteQueryDto $query): void
    {
        foreach ($query->scope->userIds() as $userId) {
            $this->invalidateAfterWrite($userId);
        }
    }
}
