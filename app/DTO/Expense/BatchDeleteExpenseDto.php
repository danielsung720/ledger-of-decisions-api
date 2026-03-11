<?php

declare(strict_types=1);

namespace App\DTO\Expense;

/**
 * Data object for batch deleting expense records.
 */
final readonly class BatchDeleteExpenseDto
{
    /**
     * @param  array<int>  $ids  Expense ids to delete.
     */
    public function __construct(
        public array $ids
    ) {
    }

    /**
     * Build DTO from validated request payload.
     *
     * @param  array{ids: array<int>}  $payload
     */
    public static function fromArray(array $payload): self
    {
        $ids = array_values(array_map('intval', $payload['ids']));

        return new self(
            ids: $ids
        );
    }
}
