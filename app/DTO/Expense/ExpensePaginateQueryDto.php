<?php

declare(strict_types=1);

namespace App\DTO\Expense;

use App\Support\AccessScope;

/**
 * Query object for expense pagination with scope and filters.
 */
final readonly class ExpensePaginateQueryDto
{
    /**
     * @param  AccessScope  $scope  Data access boundary for current caller.
     * @param  ExpenseFiltersDto  $filters  Filter options for list endpoint.
     */
    public function __construct(
        public AccessScope $scope,
        public ExpenseFiltersDto $filters
    ) {
    }

    /**
     * Build query object for a single-user scope.
     */
    public static function forUser(int $userId, ExpenseFiltersDto $filters): self
    {
        return new self(
            scope: AccessScope::forUser($userId),
            filters: $filters,
        );
    }
}
