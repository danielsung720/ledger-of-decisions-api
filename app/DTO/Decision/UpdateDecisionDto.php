<?php

declare(strict_types=1);

namespace App\DTO\Decision;

/**
 * Data object carrying decision updates.
 */
final readonly class UpdateDecisionDto
{
    /**
     * @param  array<string, mixed>  $attributes  Validated, fillable update fields.
     */
    public function __construct(
        public array $attributes
    ) {
    }

    /**
     * Build DTO from validated update payload.
     *
     * @param  array{intent: string, confidence_level?: string, decision_note?: string|null}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(attributes: $payload);
    }

    /**
     * Return persistence-ready update payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
