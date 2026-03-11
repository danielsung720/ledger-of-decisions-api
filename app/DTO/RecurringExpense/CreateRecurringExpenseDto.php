<?php

declare(strict_types=1);

namespace App\DTO\RecurringExpense;

use Carbon\Carbon;

/**
 * Data object for creating a recurring expense from validated request payload.
 */
final readonly class CreateRecurringExpenseDto
{
    /**
     * @param  string  $name  Recurring expense name.
     * @param  float  $amountMin  Minimum generated amount.
     * @param  float|null  $amountMax  Maximum generated amount.
     * @param  string|null  $currency  ISO currency code, null uses default.
     * @param  string  $category  Category enum value.
     * @param  string  $frequencyType  Frequency type enum value.
     * @param  int|null  $frequencyInterval  Recurrence interval.
     * @param  int|null  $dayOfMonth  Day selector for monthly/yearly recurrences.
     * @param  int|null  $monthOfYear  Month selector for yearly recurrences.
     * @param  int|null  $dayOfWeek  Day selector for weekly recurrences.
     * @param  string  $startDate  Start date in Y-m-d format.
     * @param  string|null  $endDate  Optional end date in Y-m-d format.
     * @param  string|null  $defaultIntent  Optional default intent enum value.
     * @param  string|null  $note  Optional note.
     * @param  string  $nextOccurrence  Next generation date in Y-m-d format.
     */
    public function __construct(
        public string $name,
        public float $amountMin,
        public ?float $amountMax,
        public ?string $currency,
        public string $category,
        public string $frequencyType,
        public ?int $frequencyInterval,
        public ?int $dayOfMonth,
        public ?int $monthOfYear,
        public ?int $dayOfWeek,
        public string $startDate,
        public ?string $endDate,
        public ?string $defaultIntent,
        public ?string $note,
        public string $nextOccurrence
    ) {
    }

    /**
     * Build DTO from validated request payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $startDate = (string) $payload['start_date'];

        return new self(
            name: (string) $payload['name'],
            amountMin: (float) $payload['amount_min'],
            amountMax: array_key_exists('amount_max', $payload) && $payload['amount_max'] !== null ? (float) $payload['amount_max'] : null,
            currency: array_key_exists('currency', $payload) ? (string) $payload['currency'] : null,
            category: (string) $payload['category'],
            frequencyType: (string) $payload['frequency_type'],
            frequencyInterval: array_key_exists('frequency_interval', $payload) && $payload['frequency_interval'] !== null ? (int) $payload['frequency_interval'] : null,
            dayOfMonth: array_key_exists('day_of_month', $payload) && $payload['day_of_month'] !== null ? (int) $payload['day_of_month'] : null,
            monthOfYear: array_key_exists('month_of_year', $payload) && $payload['month_of_year'] !== null ? (int) $payload['month_of_year'] : null,
            dayOfWeek: array_key_exists('day_of_week', $payload) && $payload['day_of_week'] !== null ? (int) $payload['day_of_week'] : null,
            startDate: $startDate,
            endDate: array_key_exists('end_date', $payload) && $payload['end_date'] !== null ? (string) $payload['end_date'] : null,
            defaultIntent: array_key_exists('default_intent', $payload) && $payload['default_intent'] !== null ? (string) $payload['default_intent'] : null,
            note: array_key_exists('note', $payload) && $payload['note'] !== null ? (string) $payload['note'] : null,
            nextOccurrence: Carbon::parse($startDate)->toDateString(),
        );
    }

    /**
     * Convert DTO back to persistence payload and drop null values.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'amount_min' => $this->amountMin,
            'amount_max' => $this->amountMax,
            'currency' => $this->currency,
            'category' => $this->category,
            'frequency_type' => $this->frequencyType,
            'frequency_interval' => $this->frequencyInterval,
            'day_of_month' => $this->dayOfMonth,
            'month_of_year' => $this->monthOfYear,
            'day_of_week' => $this->dayOfWeek,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'default_intent' => $this->defaultIntent,
            'note' => $this->note,
            'next_occurrence' => $this->nextOccurrence,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
