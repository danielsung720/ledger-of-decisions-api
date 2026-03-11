<?php

declare(strict_types=1);

namespace App\DTO\Income;

use App\Enums\CashFlowFrequencyType;

/**
 * Normalized filter object for income listing requests.
 */
final readonly class IncomeFiltersDto
{
    /**
     * @param  bool|null  $isActive  Active flag for a target month.
     * @param  array<string>  $frequencyTypes
     * @param  int  $perPage  Pagination size after request normalization.
     */
    public function __construct(
        public ?bool $isActive,
        public array $frequencyTypes,
        public int $perPage
    ) {
    }

    /**
     * Normalize and sanitize raw filter input.
     *
     * @param  array{is_active?: bool, frequency_type?: array<string>, per_page: int}  $filters
     */
    public static function fromArray(array $filters): self
    {
        $frequencyTypes = array_values(array_filter(
            $filters['frequency_type'] ?? [],
            static fn (string $type): bool => CashFlowFrequencyType::tryFrom($type) !== null
        ));

        return new self(
            isActive: $filters['is_active'] ?? null,
            frequencyTypes: $frequencyTypes,
            perPage: $filters['per_page'],
        );
    }
}
