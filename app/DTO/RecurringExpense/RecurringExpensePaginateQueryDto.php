<?php

declare(strict_types=1);

namespace App\DTO\RecurringExpense;

use App\Support\AccessScope;

/**
 * Query object for recurring expense pagination with scope and filters.
 */
final readonly class RecurringExpensePaginateQueryDto
{
    /**
     * @param  AccessScope  $scope  Data access boundary for current caller.
     * @param  RecurringExpenseFiltersDto  $filters  Filter options for list endpoint.
     */
    public function __construct(
        public AccessScope $scope,
        public RecurringExpenseFiltersDto $filters
    ) {
    }

    /**
     * Build query object for a single-user scope.
     */
    public static function forUser(int $userId, RecurringExpenseFiltersDto $filters): self
    {
        return new self(
            scope: AccessScope::forUser($userId),
            filters: $filters,
        );
    }
}
