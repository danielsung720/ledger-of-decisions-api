<?php

declare(strict_types=1);

namespace App\DTO\RecurringExpense;

use App\Support\AccessScope;

/**
 * Query object for upcoming recurring expenses endpoint.
 */
final readonly class RecurringExpenseUpcomingQueryDto
{
    /**
     * @param  AccessScope  $scope  Data access boundary for current caller.
     * @param  int  $days  Day-window size for upcoming filter.
     */
    public function __construct(
        public AccessScope $scope,
        public int $days
    ) {
    }

    /**
     * Build query object for a single-user scope.
     */
    public static function forUser(int $userId, int $days): self
    {
        return new self(
            scope: AccessScope::forUser($userId),
            days: $days
        );
    }
}
