<?php

declare(strict_types=1);

namespace App\DTO\Entry;

/**
 * Decision part of a combined entry creation payload.
 */
final readonly class CreateEntryDecisionDto
{
    /**
     * @param  string  $intent  Decision intent enum value.
     * @param  string|null  $confidenceLevel  Optional confidence level enum value.
     * @param  string|null  $decisionNote  Optional decision note.
     */
    public function __construct(
        public string $intent,
        public ?string $confidenceLevel,
        public ?string $decisionNote
    ) {
    }

    /**
     * Build decision DTO segment from validated payload.
     *
     * @param  array{intent: string, confidence_level?: string, decision_note?: string|null}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            intent: $payload['intent'],
            confidenceLevel: $payload['confidence_level'] ?? null,
            decisionNote: $payload['decision_note'] ?? null,
        );
    }

    /**
     * Convert DTO segment to persistence payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'intent' => $this->intent,
            'confidence_level' => $this->confidenceLevel,
            'decision_note' => $this->decisionNote,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
