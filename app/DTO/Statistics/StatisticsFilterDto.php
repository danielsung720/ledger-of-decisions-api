<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

use App\Enums\DatePreset;

/**
 * Normalized date filter for statistics queries.
 */
final readonly class StatisticsFilterDto
{
    /**
     * @param  string|null  $startDate  Start date in Y-m-d format.
     * @param  string|null  $endDate  End date in Y-m-d format.
     * @param  DatePreset|null  $preset  Optional preset range (today/week/month).
     */
    public function __construct(
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?DatePreset $preset = null
    ) {
    }

    /**
     * Build filter DTO from validated request input.
     *
     * @param  array{start_date?: string, end_date?: string, preset?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            preset: isset($data['preset']) ? DatePreset::tryFrom((string) $data['preset']) : null,
        );
    }
}
