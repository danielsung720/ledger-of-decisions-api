<?php

declare(strict_types=1);

namespace App\DTO\Entry;

/**
 * Expense part of a combined entry creation payload.
 */
final readonly class CreateEntryExpenseDto
{
    /**
     * @param  float  $amount  Expense amount in major unit.
     * @param  string|null  $currency  ISO currency code, null uses default.
     * @param  string  $category  Category enum value.
     * @param  string  $occurredAt  Occurred datetime string.
     * @param  string|null  $note  Optional note.
     */
    public function __construct(
        public float $amount,
        public ?string $currency,
        public string $category,
        public string $occurredAt,
        public ?string $note
    ) {
    }

    /**
     * Build expense DTO segment from validated payload.
     *
     * @param  array{amount: int|float|string, currency?: string, category: string, occurred_at: string, note?: string|null}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            amount: (float) $payload['amount'],
            currency: $payload['currency'] ?? null,
            category: $payload['category'],
            occurredAt: $payload['occurred_at'],
            note: $payload['note'] ?? null,
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
            'amount' => $this->amount,
            'currency' => $this->currency,
            'category' => $this->category,
            'occurred_at' => $this->occurredAt,
            'note' => $this->note,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
