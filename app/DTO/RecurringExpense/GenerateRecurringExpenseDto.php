<?php

declare(strict_types=1);

namespace App\DTO\RecurringExpense;

use Carbon\Carbon;

/**
 * Data object for manual recurring-expense generation options.
 */
final readonly class GenerateRecurringExpenseDto
{
    /**
     * @param  Carbon|null  $date  Optional generation date.
     * @param  string|null  $amount  Optional override amount.
     */
    public function __construct(
        public ?Carbon $date,
        public ?string $amount
    ) {
    }

    /**
     * Build DTO from validated request payload.
     *
     * @param  array{date?: string|null, amount?: int|float|string|null}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            date: array_key_exists('date', $payload) && $payload['date'] !== null ? Carbon::parse((string) $payload['date']) : null,
            amount: array_key_exists('amount', $payload) && $payload['amount'] !== null ? (string) $payload['amount'] : null,
        );
    }
}
