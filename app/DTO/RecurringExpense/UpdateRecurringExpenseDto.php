<?php

declare(strict_types=1);

namespace App\DTO\RecurringExpense;

/**
 * Data object carrying partial recurring expense updates.
 */
final readonly class UpdateRecurringExpenseDto
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
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        if (array_key_exists('amount_min', $payload) && $payload['amount_min'] !== null) {
            $payload['amount_min'] = (float) $payload['amount_min'];
        }

        if (array_key_exists('amount_max', $payload) && $payload['amount_max'] !== null) {
            $payload['amount_max'] = (float) $payload['amount_max'];
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
