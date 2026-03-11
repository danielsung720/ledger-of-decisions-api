<?php

declare(strict_types=1);

namespace App\DTO\Expense;

/**
 * Data object carrying partial expense updates.
 */
final readonly class UpdateExpenseDto
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
     * @param  array{amount?: int|float|string, currency?: string, category?: string, occurred_at?: string, note?: string|null}  $payload
     */
    public static function fromArray(array $payload): self
    {
        if (array_key_exists('amount', $payload)) {
            $payload['amount'] = (float) $payload['amount'];
        }

        return new self(
            attributes: $payload
        );
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
