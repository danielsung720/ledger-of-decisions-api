<?php

declare(strict_types=1);

namespace App\DTO\CashFlow;

use App\Support\AccessScope;

/**
 * Query object for cash flow summary endpoint.
 */
final readonly class CashFlowSummaryQueryDto
{
    /**
     * @param  AccessScope  $scope  Data access boundary for current caller.
     */
    public function __construct(
        public AccessScope $scope
    ) {
    }

    /**
     * Build query object for a single-user scope.
     */
    public static function forUser(int $userId): self
    {
        return new self(
            scope: AccessScope::forUser($userId),
        );
    }
}
