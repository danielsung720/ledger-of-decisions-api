<?php

declare(strict_types=1);

namespace App\DTO\CashFlowItem;

use App\Enums\CashFlowFrequencyType;
use App\Enums\Category;

/**
 * Normalized filter object for cash flow item listing requests.
 */
final readonly class CashFlowItemFiltersDto
{
    /**
     * @param  array<string>  $categories  Category enum values.
     * @param  bool|null  $isActive  Active flag filter.
     * @param  array<string>  $frequencyTypes
     * @param  int  $perPage  Pagination size after request normalization.
     */
    public function __construct(
        public array $categories,
        public ?bool $isActive,
        public array $frequencyTypes,
        public int $perPage
    ) {
    }

    /**
     * Normalize and sanitize raw filter input.
     *
     * @param  array{category?: array<string>, is_active?: bool, frequency_type?: array<string>, per_page: int}  $filters
     */
    public static function fromArray(array $filters): self
    {
        $categories = array_values(array_filter(
            $filters['category'] ?? [],
            static fn (string $category): bool => Category::tryFrom($category) !== null
        ));

        $frequencyTypes = array_values(array_filter(
            $filters['frequency_type'] ?? [],
            static fn (string $type): bool => CashFlowFrequencyType::tryFrom($type) !== null
        ));

        return new self(
            categories: $categories,
            isActive: $filters['is_active'] ?? null,
            frequencyTypes: $frequencyTypes,
            perPage: $filters['per_page'],
        );
    }
}
