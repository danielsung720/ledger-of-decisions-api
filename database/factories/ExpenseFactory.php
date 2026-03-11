<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'user_id' => auth()->id() ?? User::factory(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency' => 'TWD',
            'category' => fake()->randomElement(Category::cases()),
            'occurred_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'note' => fake()->optional()->sentence(),
        ];
    }

    public function food(): static
    {
        return $this->state(fn () => ['category' => Category::Food]);
    }

    public function transport(): static
    {
        return $this->state(fn () => ['category' => Category::Transport]);
    }
}
