<?php

declare(strict_types=1);

namespace App\DTO\CashFlowItem;

/**
 * Data object carrying partial cash flow item updates.
 */
final readonly class UpdateCashFlowItemDto
{
    /**
     * @param  array<string, mixed>  $payload  Validated, fillable update fields.
     */
    public function __construct(
        private array $payload
    ) {
    }

    /**
     * Build DTO from validated update payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self($payload);
    }

    /**
     * Return persistence-ready update payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }
}
