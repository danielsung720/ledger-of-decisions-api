<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Http\Resources\RecurringExpenseResource;
use App\Models\Expense;
use App\Models\RecurringExpense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecurringExpenseResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ToArrayShouldFormatRangeAndInterval(): void
    {
        $recurringExpense = RecurringExpense::factory()->weekly()->withAmountRange(100, 200)->create([
            'frequency_interval' => 2,
        ]);
        Expense::factory()->count(2)->create([
            'user_id' => $recurringExpense->user_id,
            'recurring_expense_id' => $recurringExpense->id,
        ]);
        $recurringExpense->loadCount('expenses');

        $data = (new RecurringExpenseResource($recurringExpense))->toArray(new Request());

        $this->assertSame('$100.00 ~ $200.00', $data['amount_display']);
        $this->assertSame('每 2 週', $data['frequency_display']);
        $this->assertTrue($data['has_amount_range']);
        $this->assertSame(2, $data['expenses_count']);
    }

    #[Test]
    public function ToArrayShouldFormatSingleAmountAndDefaultInterval(): void
    {
        $recurringExpense = RecurringExpense::factory()->monthly()->create([
            'amount_min' => 500,
            'amount_max' => null,
            'frequency_interval' => 1,
        ]);

        $data = (new RecurringExpenseResource($recurringExpense))->toArray(new Request());

        $this->assertSame('$500.00', $data['amount_display']);
        $this->assertSame('每月', $data['frequency_display']);
        $this->assertFalse($data['has_amount_range']);
    }

    #[Test]
    public function ToArrayShouldFormatDailyIntervalFrequencyDisplay(): void
    {
        $recurringExpense = RecurringExpense::factory()->daily()->create([
            'frequency_interval' => 3,
        ]);

        $data = (new RecurringExpenseResource($recurringExpense))->toArray(new Request());

        $this->assertSame('每 3 天', $data['frequency_display']);
    }

    #[Test]
    public function ToArrayShouldFormatYearlyIntervalFrequencyDisplay(): void
    {
        $recurringExpense = RecurringExpense::factory()->yearly()->create([
            'frequency_interval' => 2,
        ]);

        $data = (new RecurringExpenseResource($recurringExpense))->toArray(new Request());

        $this->assertSame('每 2 年', $data['frequency_display']);
    }

    #[Test]
    public function ToArrayShouldFormatMonthlyIntervalFrequencyDisplay(): void
    {
        $recurringExpense = RecurringExpense::factory()->monthly()->create([
            'frequency_interval' => 2,
        ]);

        $data = (new RecurringExpenseResource($recurringExpense))->toArray(new Request());

        $this->assertSame('每 2 月', $data['frequency_display']);
    }
}
