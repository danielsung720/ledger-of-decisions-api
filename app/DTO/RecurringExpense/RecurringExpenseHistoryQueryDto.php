<?php

declare(strict_types=1);

namespace App\DTO\RecurringExpense;

/**
 * Query object for recurring expense history endpoint.
 */
final readonly class RecurringExpenseHistoryQueryDto
{
    /**
     * @param  int  $limit  Max number of history rows to return.
     */
    public function __construct(
        public int $limit
    ) {
    }

    /**
     * Build DTO from normalized request filters.
     *
     * @param  array{limit: int}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(limit: $payload['limit']);
    }
}
