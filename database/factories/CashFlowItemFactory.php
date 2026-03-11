<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CashFlowFrequencyType;
use App\Enums\Category;
use App\Models\CashFlowItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashFlowItem>
 */
class CashFlowItemFactory extends Factory
{
    protected $model = CashFlowItem::class;

    public function definition(): array
    {
        $startDate = Carbon::today();

        return [
            'user_id' => auth()->id() ?? User::factory(),
            'name' => fake()->randomElement(['房租', '電費', '網路費', '伙食費', '交通費', '保險']),
            'amount' => fake()->randomFloat(2, 1000, 30000),
            'currency' => 'TWD',
            'category' => fake()->randomElement(Category::cases()),
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

    public function living(): static
    {
        return $this->state(fn () => ['category' => Category::Living]);
    }

    public function food(): static
    {
        return $this->state(fn () => ['category' => Category::Food]);
    }

    public function withInterval(int $interval): static
    {
        return $this->state(fn () => [
            'frequency_interval' => $interval,
        ]);
    }
}
