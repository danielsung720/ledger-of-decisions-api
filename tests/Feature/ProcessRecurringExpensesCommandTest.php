<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Category;
use App\Enums\FrequencyType;
use App\Enums\Intent;
use App\Models\RecurringExpense;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class ProcessRecurringExpensesCommandTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
    }

    #[Test]
    public function ProcessesDueRecurringExpenses(): void
    {
        Carbon::setTestNow('2026-02-15');

        $recurringExpense = RecurringExpense::factory()->create([
            'name' => '車貸',
            'amount_min' => 15000,
            'category' => Category::Living,
            'frequency_type' => FrequencyType::Monthly,
            'frequency_interval' => 1,
            'day_of_month' => 15,
            'start_date' => '2026-01-15',
            'next_occurrence' => '2026-02-15',
            'default_intent' => Intent::Necessity,
            'is_active' => true,
        ]);

        $this->artisan('recurring-expenses:process')
            ->assertSuccessful()
            ->expectsOutputToContain('已產生 1 筆消費記錄');

        $this->assertDatabaseHas('expenses', [
            'recurring_expense_id' => $recurringExpense->id,
            'amount' => 15000,
            'category' => 'living',
        ]);

        // Verify next occurrence was updated
        $recurringExpense->refresh();
        $this->assertEquals('2026-03-15', $recurringExpense->next_occurrence->toDateString());
    }

    #[Test]
    public function SkipsInactiveRecurringExpenses(): void
    {
        Carbon::setTestNow('2026-02-15');

        RecurringExpense::factory()->create([
            'next_occurrence' => '2026-02-15',
            'is_active' => false,
        ]);

        $this->artisan('recurring-expenses:process')
            ->assertSuccessful()
            ->expectsOutputToContain('沒有需要處理的固定支出');

        $this->assertDatabaseCount('expenses', 0);
    }

    #[Test]
    public function ProcessesMissedOccurrences(): void
    {
        Carbon::setTestNow('2026-02-17');

        RecurringExpense::factory()->create([
            'frequency_type' => FrequencyType::Daily,
            'frequency_interval' => 1,
            'start_date' => '2026-02-14',
            'next_occurrence' => '2026-02-14',
            'is_active' => true,
        ]);

        $this->artisan('recurring-expenses:process')
            ->assertSuccessful();

        // Should have 4 expenses: 2/14, 2/15, 2/16, 2/17
        $this->assertDatabaseCount('expenses', 4);
    }

    #[Test]
    public function DryRunDoesNotCreateExpenses(): void
    {
        Carbon::setTestNow('2026-02-15');

        RecurringExpense::factory()->create([
            'next_occurrence' => '2026-02-15',
            'is_active' => true,
        ]);

        $this->artisan('recurring-expenses:process', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('乾跑模式');

        $this->assertDatabaseCount('expenses', 0);
    }

    #[Test]
    public function HandlesMonthEndEdgeCase(): void
    {
        Carbon::setTestNow('2026-02-28');

        // Create recurring expense for the 31st of each month
        $recurringExpense = RecurringExpense::factory()->create([
            'frequency_type' => FrequencyType::Monthly,
            'frequency_interval' => 1,
            'day_of_month' => 31,
            'start_date' => '2026-01-31',
            'next_occurrence' => '2026-02-28', // Feb doesn't have 31st
            'is_active' => true,
        ]);

        $this->artisan('recurring-expenses:process')
            ->assertSuccessful();

        $this->assertDatabaseCount('expenses', 1);

        // Next occurrence should be March 31
        $recurringExpense->refresh();
        $this->assertEquals('2026-03-31', $recurringExpense->next_occurrence->toDateString());
    }

    #[Test]
    public function DeactivatesExpiredRecurringExpenses(): void
    {
        Carbon::setTestNow('2026-02-15');

        $recurringExpense = RecurringExpense::factory()->create([
            'frequency_type' => FrequencyType::Monthly,
            'frequency_interval' => 1,
            'start_date' => '2026-01-15',
            'end_date' => '2026-02-15',
            'next_occurrence' => '2026-02-15',
            'is_active' => true,
        ]);

        $this->artisan('recurring-expenses:process')
            ->assertSuccessful();

        $recurringExpense->refresh();
        $this->assertFalse($recurringExpense->is_active);
    }

    #[Test]
    public function ProcessesUsingExplicitDateOption(): void
    {
        Carbon::setTestNow('2026-02-10');

        $recurringExpense = RecurringExpense::factory()->create([
            'name' => '電話費',
            'amount_min' => 999,
            'category' => Category::Living,
            'frequency_type' => FrequencyType::Monthly,
            'frequency_interval' => 1,
            'day_of_month' => 15,
            'start_date' => '2026-01-15',
            'next_occurrence' => '2026-02-15',
            'default_intent' => Intent::Necessity,
            'is_active' => true,
        ]);

        $this->artisan('recurring-expenses:process', ['--date' => '2026-02-15'])
            ->assertSuccessful()
            ->expectsOutputToContain('處理日期：2026-02-15');

        $this->assertDatabaseHas('expenses', [
            'recurring_expense_id' => $recurringExpense->id,
            'amount' => 999,
            'category' => 'living',
        ]);
    }

    #[Test]
    public function DryRunOutputsNoDueExpensesMessage(): void
    {
        Carbon::setTestNow('2026-02-15');

        RecurringExpense::factory()->create([
            'next_occurrence' => '2026-02-16',
            'is_active' => true,
        ]);

        $this->artisan('recurring-expenses:process', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('沒有需要處理的固定支出');
    }

    #[Test]
    public function DryRunShowsAmountRangeForVariableRecurringExpense(): void
    {
        Carbon::setTestNow('2026-02-15');

        RecurringExpense::factory()->create([
            'name' => '交通費',
            'amount_min' => 500,
            'amount_max' => 700,
            'category' => Category::Transport,
            'frequency_type' => FrequencyType::Monthly,
            'frequency_interval' => 1,
            'day_of_month' => 15,
            'start_date' => '2026-01-15',
            'next_occurrence' => '2026-02-15',
            'is_active' => true,
        ]);

        $this->artisan('recurring-expenses:process', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('$500.00 ~ $700.00');
    }
}
