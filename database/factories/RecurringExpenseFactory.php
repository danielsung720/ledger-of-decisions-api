<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Category;
use App\Enums\FrequencyType;
use App\Enums\Intent;
use App\Models\RecurringExpense;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringExpense>
 */
class RecurringExpenseFactory extends Factory
{
    protected $model = RecurringExpense::class;

    public function definition(): array
    {
        $startDate = Carbon::today();
        $frequencyType = fake()->randomElement(FrequencyType::cases());

        return [
            'user_id' => auth()->id() ?? User::factory(),
            'name' => fake()->randomElement(['車貸', 'Netflix', '電費', '網路費', '房租', '保險', '健身房會費', 'Spotify']),
            'amount_min' => fake()->randomFloat(2, 100, 5000),
            'amount_max' => null,
            'currency' => 'TWD',
            'category' => fake()->randomElement(Category::cases()),
            'frequency_type' => $frequencyType,
            'frequency_interval' => 1,
            'day_of_month' => $frequencyType === FrequencyType::Monthly || $frequencyType === FrequencyType::Yearly
                ? fake()->numberBetween(1, 28)
                : null,
            'month_of_year' => $frequencyType === FrequencyType::Yearly
                ? fake()->numberBetween(1, 12)
                : null,
            'day_of_week' => $frequencyType === FrequencyType::Weekly
                ? fake()->numberBetween(0, 6)
                : null,
            'start_date' => $startDate,
            'end_date' => null,
            'next_occurrence' => $startDate,
            'default_intent' => fake()->optional()->randomElement(Intent::cases()),
            'note' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function monthly(): static
    {
        return $this->state(fn () => [
            'frequency_type' => FrequencyType::Monthly,
            'day_of_month' => fake()->numberBetween(1, 28),
            'day_of_week' => null,
            'month_of_year' => null,
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn () => [
            'frequency_type' => FrequencyType::Weekly,
            'day_of_week' => fake()->numberBetween(0, 6),
            'day_of_month' => null,
            'month_of_year' => null,
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn () => [
            'frequency_type' => FrequencyType::Daily,
            'day_of_month' => null,
            'day_of_week' => null,
            'month_of_year' => null,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn () => [
            'frequency_type' => FrequencyType::Yearly,
            'day_of_month' => fake()->numberBetween(1, 28),
            'month_of_year' => fake()->numberBetween(1, 12),
            'day_of_week' => null,
        ]);
    }

    public function withAmountRange(float $min = 500, float $max = 2000): static
    {
        return $this->state(fn () => [
            'amount_min' => $min,
            'amount_max' => $max,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function dueToday(): static
    {
        return $this->state(fn () => [
            'next_occurrence' => Carbon::today(),
        ]);
    }

    public function duePast(): static
    {
        return $this->state(fn () => [
            'next_occurrence' => Carbon::today()->subDays(3),
        ]);
    }

    public function living(): static
    {
        return $this->state(fn () => ['category' => Category::Living]);
    }
}
