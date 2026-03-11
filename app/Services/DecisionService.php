<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Decision\CreateDecisionDto;
use App\DTO\Decision\UpdateDecisionDto;
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
        private readonly DecisionRepository $decisionRepository
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

        return $this->decisionRepository->create($scope, $expense, $payload);
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

        return $this->decisionRepository->update($scope, $expense, $payload);
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

        return true;
    }

    private function ensureExpenseInScope(AccessScope $scope, Expense $expense): void
    {
        if (! in_array((int) $expense->user_id, $scope->userIds(), true)) {
            throw (new ModelNotFoundException())->setModel(Expense::class, [$expense->id]);
        }
    }
}
