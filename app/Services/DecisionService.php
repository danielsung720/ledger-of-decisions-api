<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Decision\CreateDecisionDto;
use App\DTO\Decision\UpdateDecisionDto;
use App\Enums\CacheDomainEnum;
use App\Models\Decision;
use App\Models\Expense;
use App\Repositories\DecisionRepository;
use App\Support\AccessScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Application service for expense decision lifecycle.
 */
class DecisionService
{
    public function __construct(
        private readonly DecisionRepository $decisionRepository,
        private readonly ?ApiReadCacheService $apiReadCacheService = null
    ) {
    }

    /**
     * Create decision for a given expense if one does not already exist.
     */
    public function createForExpense(AccessScope $scope, Expense $expense, CreateDecisionDto $payload): ?Decision
    {
        $this->ensureExpenseInScope($scope, $expense);

        if ($expense->decision !== null) {
            return null;
        }

        $decision = $this->decisionRepository->create($scope, $expense, $payload);
        $this->invalidateAfterWrite((int) $expense->user_id);

        return $decision;
    }

    /**
     * Retrieve decision for a given expense.
     */
    public function showForExpense(AccessScope $scope, Expense $expense): ?Decision
    {
        return $this->decisionRepository->show($scope, $expense);
    }

    /**
     * Update decision for a given expense.
     */
    public function updateForExpense(AccessScope $scope, Expense $expense, UpdateDecisionDto $payload): ?Decision
    {
        $this->ensureExpenseInScope($scope, $expense);

        if ($expense->decision === null) {
            return null;
        }

        $updatedDecision = $this->decisionRepository->update($scope, $expense, $payload);
        $this->invalidateAfterWrite((int) $expense->user_id);

        return $updatedDecision;
    }

    /**
     * Delete decision for a given expense.
     */
    public function deleteForExpense(AccessScope $scope, Expense $expense): bool
    {
        $this->ensureExpenseInScope($scope, $expense);

        if ($expense->decision === null) {
            return false;
        }

        $this->decisionRepository->delete($scope, $expense);
        $this->invalidateAfterWrite((int) $expense->user_id);

        return true;
    }

    private function ensureExpenseInScope(AccessScope $scope, Expense $expense): void
    {
        if (! in_array((int) $expense->user_id, $scope->userIds(), true)) {
            throw (new ModelNotFoundException())->setModel(Expense::class, [$expense->id]);
        }
    }

    private function invalidateAfterWrite(int $userId): void
    {
        $cacheService = $this->apiReadCacheService ?? app(ApiReadCacheService::class);
        $cacheService->invalidateDomainVersion($userId, CacheDomainEnum::Statistics);
        $cacheService->invalidateDomainVersion($userId, CacheDomainEnum::Expenses);
    }
}
