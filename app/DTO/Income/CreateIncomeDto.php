<?php

declare(strict_types=1);

namespace App\DTO\Income;

/**
 * Data object for creating an income record from validated request payload.
 */
final readonly class CreateIncomeDto
{
    /**
     * @param  string  $name  Income name shown in UI and reports.
     * @param  float  $amount  Income amount in major unit (for example: 1200.50).
     * @param  string|null  $currency  ISO currency code, null uses model default.
     * @param  string  $frequencyType  Recurrence type value from CashFlowFrequencyType.
     * @param  int|null  $frequencyInterval  Interval used by recurrence rule.
     * @param  string  $startDate  Start date in Y-m-d format.
     * @param  string|null  $endDate  Optional end date in Y-m-d format.
     * @param  string|null  $note  Optional user note.
     */
    public function __construct(
        public string $name,
        public float $amount,
        public ?string $currency,
        public string $frequencyType,
        public ?int $frequencyInterval,
        public string $startDate,
        public ?string $endDate,
        public ?string $note
    ) {
    }

    /**
     * Build DTO from request payload keys.
     *
     * @param  array{name: string, amount: int|float|string, currency?: string, frequency_type: string, frequency_interval?: int, start_date: string, end_date?: string|null, note?: string|null}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            name: $payload['name'],
            amount: (float) $payload['amount'],
            currency: $payload['currency'] ?? null,
            frequencyType: $payload['frequency_type'],
            frequencyInterval: $payload['frequency_interval'] ?? null,
            startDate: $payload['start_date'],
            endDate: $payload['end_date'] ?? null,
            note: $payload['note'] ?? null,
        );
    }

    /**
     * Convert DTO back to persistence payload and drop null values.
     *
     * @return array<string, mixed> Array keys follow database column naming.
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'frequency_type' => $this->frequencyType,
            'frequency_interval' => $this->frequencyInterval,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'note' => $this->note,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
