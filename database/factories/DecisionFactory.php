<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ConfidenceLevel;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Decision>
 */
class DecisionFactory extends Factory
{
    protected $model = Decision::class;

    public function definition(): array
    {
        return [
            'expense_id' => Expense::factory(),
            'intent' => fake()->randomElement(Intent::cases()),
            'confidence_level' => fake()->randomElement(ConfidenceLevel::cases()),
            'decision_note' => fake()->optional()->sentence(),
        ];
    }

    public function necessity(): static
    {
        return $this->state(fn () => ['intent' => Intent::Necessity]);
    }

    public function impulse(): static
    {
        return $this->state(fn () => ['intent' => Intent::Impulse]);
    }

    public function highConfidence(): static
    {
        return $this->state(fn () => ['confidence_level' => ConfidenceLevel::High]);
    }
}
