<?php

declare(strict_types=1);

namespace App\DTO\CashFlowItem;

use App\Support\AccessScope;

/**
 * Query object for cash flow item pagination with scope and filters.
 */
final readonly class CashFlowItemPaginateQueryDto
{
    /**
     * @param  AccessScope  $scope  Data access boundary for current caller.
     * @param  CashFlowItemFiltersDto  $filters  Filter options for list endpoint.
     */
    public function __construct(
        public AccessScope $scope,
        public CashFlowItemFiltersDto $filters
    ) {
    }

    /**
     * Build query object for a single-user scope.
     */
    public static function forUser(int $userId, CashFlowItemFiltersDto $filters): self
    {
        return new self(
            scope: AccessScope::forUser($userId),
            filters: $filters,
        );
    }
}
