<?php

declare(strict_types=1);

namespace App\DTO\Income;

use App\Support\AccessScope;

/**
 * Query object for income pagination with access scope and normalized filters.
 */
final readonly class IncomePaginateQueryDto
{
    /**
     * @param  AccessScope  $scope  Scope constraints for current caller.
     * @param  IncomeFiltersDto  $filters  Filter options for list endpoint.
     */
    public function __construct(
        public AccessScope $scope,
        public IncomeFiltersDto $filters
    ) {
    }

    /**
     * Build query object for a single-user scope.
     */
    public static function forUser(int $userId, IncomeFiltersDto $filters): self
    {
        return new self(
            scope: AccessScope::forUser($userId),
            filters: $filters,
        );
    }
}
