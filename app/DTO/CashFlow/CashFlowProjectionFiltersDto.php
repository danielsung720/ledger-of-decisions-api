<?php

declare(strict_types=1);

namespace App\DTO\CashFlow;

/**
 * Normalized filter object for cash flow projection request.
 */
final readonly class CashFlowProjectionFiltersDto
{
    /**
     * @param  int  $months  Number of months to project.
     */
    public function __construct(
        public int $months
    ) {
    }

    /**
     * Build DTO from normalized request filters.
     *
     * @param  array{months: int}  $filters
     */
    public static function fromArray(array $filters): self
    {
        return new self(
            months: $filters['months'],
        );
    }
}
