<?php

declare(strict_types=1);

namespace App\DTO\CashFlow;

use App\Support\AccessScope;

/**
 * Query object for cash flow projection endpoint.
 */
final readonly class CashFlowProjectionQueryDto
{
    /**
     * @param  AccessScope  $scope  Data access boundary for current caller.
     * @param  CashFlowProjectionFiltersDto  $filters  Projection filters.
     */
    public function __construct(
        public AccessScope $scope,
        public CashFlowProjectionFiltersDto $filters
    ) {
    }

    /**
     * Build query object for a single-user scope.
     */
    public static function forUser(int $userId, CashFlowProjectionFiltersDto $filters): self
    {
        return new self(
            scope: AccessScope::forUser($userId),
            filters: $filters,
        );
    }
}
