<?php

declare(strict_types=1);

namespace App\DTO\Expense;

use App\Enums\Category;
use App\Enums\ConfidenceLevel;
use App\Enums\DatePreset;
use App\Enums\Intent;

/**
 * Normalized filter object for expense listing requests.
 */
final readonly class ExpenseFiltersDto
{
    /**
     * @param  string|null  $startDate  Start date in Y-m-d format.
     * @param  string|null  $endDate  End date in Y-m-d format.
     * @param  DatePreset|null  $preset  Optional preset range.
     * @param  array<string>  $categories
     * @param  array<string>  $intents
     * @param  array<string>  $confidenceLevels
     * @param  int  $perPage  Pagination size after request normalization.
     */
    public function __construct(
        public ?string $startDate,
        public ?string $endDate,
        public ?DatePreset $preset,
        public array $categories,
        public array $intents,
        public array $confidenceLevels,
        public int $perPage
    ) {
    }

    /**
     * Normalize and sanitize raw filter input.
     *
     * @param  array{
     *   start_date?: string,
     *   end_date?: string,
     *   preset?: string,
     *   category?: array<string>,
     *   intent?: array<string>,
     *   confidence_level?: array<string>,
     *   per_page: int
     * }  $filters
     */
    public static function fromArray(array $filters): self
    {
        $categories = array_values(array_filter(
            $filters['category'] ?? [],
            static fn (string $category): bool => Category::tryFrom($category) !== null
        ));

        $intents = array_values(array_filter(
            $filters['intent'] ?? [],
            static fn (string $intent): bool => Intent::tryFrom($intent) !== null
        ));

        $confidenceLevels = array_values(array_filter(
            $filters['confidence_level'] ?? [],
            static fn (string $level): bool => ConfidenceLevel::tryFrom($level) !== null
        ));

        return new self(
            startDate: $filters['start_date'] ?? null,
            endDate: $filters['end_date'] ?? null,
            preset: isset($filters['preset']) ? DatePreset::tryFrom($filters['preset']) : null,
            categories: $categories,
            intents: $intents,
            confidenceLevels: $confidenceLevels,
            perPage: $filters['per_page'],
        );
    }
}
