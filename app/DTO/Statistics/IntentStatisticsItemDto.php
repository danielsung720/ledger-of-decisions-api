<?php

declare(strict_types=1);

namespace App\DTO\Statistics;

/**
 * Response item for intent statistics endpoint.
 */
final readonly class IntentStatisticsItemDto
{
    /**
     * @param  string  $intent  Intent enum value.
     * @param  string  $intentLabel  Localized intent label.
     * @param  int  $count  Number of records with this intent.
     * @param  float  $avgConfidenceScore  Rounded average confidence score.
     * @param  string  $avgConfidenceLevel  Confidence level derived from score.
     */
    public function __construct(
        public string $intent,
        public string $intentLabel,
        public int $count,
        public float $avgConfidenceScore,
        public string $avgConfidenceLevel
    ) {
    }

    /**
     * @return array{intent: string, intent_label: string, count: int, avg_confidence_score: float, avg_confidence_level: string}
     */
    public function toArray(): array
    {
        return [
            'intent' => $this->intent,
            'intent_label' => $this->intentLabel,
            'count' => $this->count,
            'avg_confidence_score' => $this->avgConfidenceScore,
            'avg_confidence_level' => $this->avgConfidenceLevel,
        ];
    }
}
