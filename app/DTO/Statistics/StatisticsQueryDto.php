<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

use App\Support\AccessScope;

/**
 * Query object for date-bounded statistics endpoints.
 */
final readonly class StatisticsQueryDto
{
    /**
     * @param  AccessScope  $scope  Data access boundary for current caller.
     * @param  StatisticsFilterDto  $filter  Date range filter for statistics.
     */
    public function __construct(
        public AccessScope $scope,
        public StatisticsFilterDto $filter
    ) {
    }

    /**
     * Build query object scoped to one authenticated user.
     */
    public static function forUser(int $userId, ?StatisticsFilterDto $filter = null): self
    {
        return new self(
            scope: AccessScope::forUser($userId),
            filter: $filter ?? new StatisticsFilterDto(),
        );
    }
}
