<?php

declare(strict_types=1);

namespace App\DTO\Expense;

use App\Support\AccessScope;

/**
 * Query object for scoped expense batch delete use-case.
 */
final readonly class ExpenseBatchDeleteQueryDto
{
    /**
     * @param  AccessScope  $scope  Data access boundary for current caller.
     * @param  BatchDeleteExpenseDto  $payload  Batch delete payload.
     */
    public function __construct(
        public AccessScope $scope,
        public BatchDeleteExpenseDto $payload
    ) {
    }

    /**
     * Build query object for a single-user scope.
     */
    public static function forUser(int $userId, BatchDeleteExpenseDto $payload): self
    {
        return new self(
            scope: AccessScope::forUser($userId),
            payload: $payload,
        );
    }
}
