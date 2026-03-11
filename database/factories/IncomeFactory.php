<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CashFlowFrequencyType;
use App\Models\Income;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
{
    protected $model = Income::class;

    public function definition(): array
    {
        $startDate = Carbon::today();

        return [
            'user_id' => auth()->id() ?? User::factory(),
            'name' => fake()->randomElement(['薪資', '年終獎金', '投資收益', '兼職收入', '租金收入', '股息']),
            'amount' => fake()->randomFloat(2, 20000, 100000),
            'currency' => 'TWD',
            'frequency_type' => CashFlowFrequencyType::Monthly,
            'frequency_interval' => 1,
            'start_date' => $startDate,
            'end_date' => null,
            'note' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function monthly(): static
    {
        return $this->state(fn () => [
            'frequency_type' => CashFlowFrequencyType::Monthly,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn () => [
            'frequency_type' => CashFlowFrequencyType::Yearly,
        ]);
    }

    public function oneTime(): static
    {
        return $this->state(fn () => [
            'frequency_type' => CashFlowFrequencyType::OneTime,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function withInterval(int $interval): static
    {
        return $this->state(fn () => [
            'frequency_interval' => $interval,
        ]);
    }
}
