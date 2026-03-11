<?php

declare(strict_types=1);

namespace App\DTO\Decision;

/**
 * Data object for creating a decision from validated request payload.
 */
final readonly class CreateDecisionDto
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
     * Build DTO from validated request payload.
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
     * Convert DTO to persistence payload.
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
