<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

/**
 * Response item for intent-level summary statistics.
 */
final readonly class IntentSummaryItemDto
{
    /**
     * @param  string  $intent  Intent enum value.
     * @param  string  $intentLabel  Localized intent label.
     * @param  float  $totalAmount  Total amount grouped by intent.
     * @param  int  $count  Number of records grouped by intent.
     */
    public function __construct(
        public string $intent,
        public string $intentLabel,
        public float $totalAmount,
        public int $count
    ) {
    }

    /**
     * @return array{intent: string, intent_label: string, total_amount: float, count: int}
     */
    public function toArray(): array
    {
        return [
            'intent' => $this->intent,
            'intent_label' => $this->intentLabel,
            'total_amount' => $this->totalAmount,
            'count' => $this->count,
        ];
    }
}
