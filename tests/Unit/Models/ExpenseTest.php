<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\Category;
use App\Models\Decision;
use App\Models\Expense;
use App\Models\RecurringExpense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ItHasCorrectFillableAttributes(): void
    {
        $expense = new Expense();
        $fillable = $expense->getFillable();

        $this->assertContains('amount', $fillable);
        $this->assertContains('currency', $fillable);
        $this->assertContains('category', $fillable);
        $this->assertContains('occurred_at', $fillable);
        $this->assertContains('note', $fillable);
        $this->assertContains('recurring_expense_id', $fillable);
    }

    #[Test]
    public function ItHasDefaultCurrency(): void
    {
        $expense = new Expense();

        $this->assertSame('TWD', $expense->currency);
    }

    #[Test]
    public function ItCastsAmountToDecimal(): void
    {
        $expense = Expense::factory()->create(['amount' => 100.50]);

        $this->assertSame('100.50', $expense->amount);
    }

    #[Test]
    public function ItCastsCategoryToEnum(): void
    {
        $expense = Expense::factory()->create(['category' => Category::Food]);

        $this->assertInstanceOf(Category::class, $expense->category);
        $this->assertSame(Category::Food, $expense->category);
    }

    #[Test]
    public function ItCastsOccurredAtToDatetime(): void
    {
        $expense = Expense::factory()->create(['occurred_at' => '2026-02-08 10:00:00']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $expense->occurred_at);
        $this->assertSame('2026-02-08', $expense->occurred_at->toDateString());
    }

    #[Test]
    public function ItHasOneDecision(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create(['expense_id' => $expense->id]);

        $this->assertInstanceOf(Decision::class, $expense->decision);
        $this->assertTrue($expense->decision->is($decision));
    }

    #[Test]
    public function ItCanHaveNoDecision(): void
    {
        $expense = Expense::factory()->create();

        $this->assertNull($expense->decision);
    }

    #[Test]
    public function ItBelongsToRecurringExpense(): void
    {
        $recurringExpense = RecurringExpense::factory()->create();
        $expense = Expense::factory()->create(['recurring_expense_id' => $recurringExpense->id]);

        $this->assertInstanceOf(RecurringExpense::class, $expense->recurringExpense);
        $this->assertTrue($expense->recurringExpense->is($recurringExpense));
    }

    #[Test]
    public function IsFromRecurringReturnsTrueWhenHasRecurringExpense(): void
    {
        $recurringExpense = RecurringExpense::factory()->create();
        $expense = Expense::factory()->create(['recurring_expense_id' => $recurringExpense->id]);

        $this->assertTrue($expense->isFromRecurring());
    }

    #[Test]
    public function IsFromRecurringReturnsFalseWhenNoRecurringExpense(): void
    {
        $expense = Expense::factory()->create(['recurring_expense_id' => null]);

        $this->assertFalse($expense->isFromRecurring());
    }

    #[Test]
    public function ItCanBeCreatedWithFactory(): void
    {
        $expense = Expense::factory()->create();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    #[Test]
    public function FoodFactoryStateCreatesFoodCategory(): void
    {
        $expense = Expense::factory()->food()->create();

        $this->assertSame(Category::Food, $expense->category);
    }

    #[Test]
    public function TransportFactoryStateCreatesTransportCategory(): void
    {
        $expense = Expense::factory()->transport()->create();

        $this->assertSame(Category::Transport, $expense->category);
    }
}
