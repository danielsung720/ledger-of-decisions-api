<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

/**
 * Response item for category-level summary statistics.
 */
final readonly class CategorySummaryItemDto
{
    /**
     * @param  string  $category  Category enum value.
     * @param  string  $categoryLabel  Localized category label.
     * @param  float  $totalAmount  Total amount grouped by category.
     * @param  int  $count  Number of expenses in category.
     */
    public function __construct(
        public string $category,
        public string $categoryLabel,
        public float $totalAmount,
        public int $count
    ) {
    }

    /**
     * @return array{category: string, category_label: string, total_amount: float, count: int}
     */
    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'category_label' => $this->categoryLabel,
            'total_amount' => $this->totalAmount,
            'count' => $this->count,
        ];
    }
}
