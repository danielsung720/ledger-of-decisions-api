<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

/**
 * Raw aggregate row for statistics grouped by expense category.
 */
final readonly class CategoryAggregateDto
{
    /**
     * @param  string  $category  Category enum value from database.
     * @param  float  $totalAmount  Total amount for this category.
     * @param  int  $count  Number of records in this category.
     */
    public function __construct(
        public string $category,
        public float $totalAmount,
        public int $count
    ) {
    }
}
