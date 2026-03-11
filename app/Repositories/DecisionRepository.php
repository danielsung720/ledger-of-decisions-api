<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\Decision\CreateDecisionDto;
use App\DTO\Decision\UpdateDecisionDto;
use App\Models\Decision;
use App\Models\Expense;
use App\Support\AccessScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Persistence operations for expense decisions.
 */
class DecisionRepository
{
    /**
     * Create a decision row for the given expense.
     */
    public function create(AccessScope $scope, Expense $expense, CreateDecisionDto $payload): Decision
    {
        $scopedExpense = $this->findScopedExpense($scope, $expense);

        /** @var Decision */
        return $scopedExpense->decision()->create($payload->toArray());
    }

    /**
     * Return decision row for the given expense.
     */
    public function show(AccessScope $scope, Expense $expense): ?Decision
    {
        $scopedExpense = $this->findScopedExpense($scope, $expense);

        /** @var Decision|null */
        return $scopedExpense->decision;
    }

    /**
     * Update one decision row.
     */
    public function update(AccessScope $scope, Expense $expense, UpdateDecisionDto $payload): ?Decision
    {
        $scopedExpense = $this->findScopedExpense($scope, $expense);
        $decision = $scopedExpense->decision;
        if ($decision === null) {
            return null;
        }

        $decision->update($payload->toArray());

        /** @var Decision */
        return $decision->refresh();
    }

    /**
     * Delete one decision row.
     */
    public function delete(AccessScope $scope, Expense $expense): bool
    {
        $scopedExpense = $this->findScopedExpense($scope, $expense);
        $decision = $scopedExpense->decision;
        if ($decision === null) {
            return false;
        }

        $decision->delete();

        return true;
    }

    private function findScopedExpense(AccessScope $scope, Expense $expense): Expense
    {
        /** @var Expense|null $found */
        $found = Expense::query()
            ->whereIn('user_id', $scope->userIds())
            ->find($expense->id);

        return $found ?? throw (new ModelNotFoundException())->setModel(Expense::class, [$expense->id]);
    }
}
