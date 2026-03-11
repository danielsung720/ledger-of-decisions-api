<?php

declare(strict_types=1);

namespace App\DTO\Entry;

/**
 * Composite data object for creating expense and decision together.
 */
final readonly class CreateEntryDto
{
    /**
     * @param  CreateEntryExpenseDto  $expense  Expense payload segment.
     * @param  CreateEntryDecisionDto  $decision  Decision payload segment.
     */
    public function __construct(
        public CreateEntryExpenseDto $expense,
        public CreateEntryDecisionDto $decision
    ) {
    }

    /**
     * Build composite DTO from validated request payload.
     *
     * @param  array{
     *   amount: int|float|string,
     *   currency?: string,
     *   category: string,
     *   occurred_at: string,
     *   note?: string|null,
     *   intent: string,
     *   confidence_level?: string,
     *   decision_note?: string|null
     * }  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            expense: CreateEntryExpenseDto::fromArray($payload),
            decision: CreateEntryDecisionDto::fromArray($payload),
        );
    }
}
