<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

/**
 * Response item for high-confidence intent trend output.
 */
final readonly class HighConfidenceIntentItemDto
{
    /**
     * @param  string  $intent  Intent enum value.
     * @param  string  $intentLabel  Localized intent label.
     * @param  int  $count  Number of matched records.
     */
    public function __construct(
        public string $intent,
        public string $intentLabel,
        public int $count
    ) {
    }

    /**
     * @return array{intent: string, intent_label: string, count: int}
     */
    public function toArray(): array
    {
        return [
            'intent' => $this->intent,
            'intent_label' => $this->intentLabel,
            'count' => $this->count,
        ];
    }
}
