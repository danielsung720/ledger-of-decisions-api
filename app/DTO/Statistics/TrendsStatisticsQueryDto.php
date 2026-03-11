<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

use App\Support\AccessScope;
use Carbon\Carbon;

/**
 * Query object for trends endpoint with precomputed week boundaries.
 */
final readonly class TrendsStatisticsQueryDto
{
    /**
     * @param  AccessScope  $scope  Data access boundary for current caller.
     * @param  Carbon  $thisWeekStart  Start of current week.
     * @param  Carbon  $thisWeekEnd  End of current week.
     * @param  Carbon  $lastWeekStart  Start of previous week.
     * @param  Carbon  $lastWeekEnd  End of previous week.
     * @param  int  $highConfidenceLimit  Max number of top intents to return.
     */
    public function __construct(
        public AccessScope $scope,
        public Carbon $thisWeekStart,
        public Carbon $thisWeekEnd,
        public Carbon $lastWeekStart,
        public Carbon $lastWeekEnd,
        public int $highConfidenceLimit = 3
    ) {
    }

    /**
     * Build trends query for one authenticated user and reference date.
     */
    public static function forUser(int $userId, Carbon $now, int $highConfidenceLimit = 3): self
    {
        return new self(
            scope: AccessScope::forUser($userId),
            thisWeekStart: $now->copy()->startOfWeek(),
            thisWeekEnd: $now->copy()->endOfWeek(),
            lastWeekStart: $now->copy()->subWeek()->startOfWeek(),
            lastWeekEnd: $now->copy()->subWeek()->endOfWeek(),
            highConfidenceLimit: $highConfidenceLimit,
        );
    }
}
