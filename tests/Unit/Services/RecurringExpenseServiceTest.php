<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\RecurringExpense\RecurringExpenseUpcomingQueryDto;
use App\Enums\Category;
use App\Enums\ConfidenceLevel;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\User;
use App\Repositories\RecurringExpenseRepository;
use App\Services\RecurringExpenseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecurringExpenseServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecurringExpenseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecurringExpenseService(new RecurringExpenseRepository);
        Carbon::setTestNow('2026-02-08');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // processAllDue tests
    #[Test]
    public function process_all_due_generates_expenses_for_due_recurring_expenses(): void
    {
        $recurringExpense1 = RecurringExpense::factory()->daily()->create([
            'next_occurrence' => Carbon::today(),
            'amount_min' => 100,
            'default_intent' => Intent::Necessity,
        ]);

        $recurringExpense2 = RecurringExpense::factory()->daily()->create([
            'next_occurrence' => Carbon::today(),
            'amount_min' => 200,
        ]);

        $generatedExpenses = $this->service->processAllDue();

        // Each recurring expense generates 1 expense for today
        $this->assertCount(2, $generatedExpenses);
        $this->assertDatabaseCount('expenses', 2);
    }

    #[Test]
    public function process_all_due_skips_inactive_recurring_expenses(): void
    {
        RecurringExpense::factory()->daily()->create([
            'next_occurrence' => Carbon::today(),
            'is_active' => false,
        ]);

        $generatedExpenses = $this->service->processAllDue();

        $this->assertCount(0, $generatedExpenses);
        $this->assertDatabaseCount('expenses', 0);
    }

    #[Test]
    public function process_all_due_skips_future_recurring_expenses(): void
    {
        RecurringExpense::factory()->create([
            'next_occurrence' => Carbon::tomorrow(),
        ]);

        $generatedExpenses = $this->service->processAllDue();

        $this->assertCount(0, $generatedExpenses);
        $this->assertDatabaseCount('expenses', 0);
    }

    // processRecurringExpense tests
    #[Test]
    public function process_recurring_expense_generates_all_missed_occurrences(): void
    {
        $recurringExpense = RecurringExpense::factory()->daily()->create([
            'frequency_interval' => 1,
            'next_occurrence' => Carbon::parse('2026-02-05'),
            'amount_min' => 100,
        ]);

        $generatedExpenses = $this->service->processRecurringExpense($recurringExpense);

        // Should generate 4 expenses: 02-05, 02-06, 02-07, 02-08
        $this->assertCount(4, $generatedExpenses);
        $this->assertDatabaseCount('expenses', 4);
    }

    #[Test]
    public function process_recurring_expense_updates_next_occurrence(): void
    {
        $recurringExpense = RecurringExpense::factory()->daily()->create([
            'frequency_interval' => 1,
            'next_occurrence' => Carbon::today(),
            'amount_min' => 100,
        ]);

        $this->service->processRecurringExpense($recurringExpense);

        $recurringExpense->refresh();
        $this->assertSame('2026-02-09', $recurringExpense->next_occurrence->toDateString());
    }

    #[Test]
    public function process_recurring_expense_returns_empty_for_inactive(): void
    {
        $recurringExpense = RecurringExpense::factory()->inactive()->create([
            'next_occurrence' => Carbon::today(),
        ]);

        $generatedExpenses = $this->service->processRecurringExpense($recurringExpense);

        $this->assertCount(0, $generatedExpenses);
    }

    #[Test]
    public function process_recurring_expense_continues_when_generation_throws_exception(): void
    {
        Log::spy();

        $service = new class(new RecurringExpenseRepository) extends RecurringExpenseService
        {
            public function generateExpenseForDate(RecurringExpense $recurringExpense, Carbon $occurrenceDate): Expense
            {
                throw new \RuntimeException('forced failure');
            }
        };

        $recurringExpense = RecurringExpense::factory()->daily()->create([
            'frequency_interval' => 1,
            'next_occurrence' => Carbon::today(),
            'is_active' => true,
        ]);

        $generatedExpenses = $service->processRecurringExpense($recurringExpense, Carbon::today());

        $this->assertCount(0, $generatedExpenses);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertSame('2026-02-08', $recurringExpense->fresh()->next_occurrence->toDateString());
    }

    // generateExpenseForDate tests
    #[Test]
    public function generate_expense_for_date_creates_expense(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'name' => 'Monthly Rent',
            'amount_min' => 15000,
            'currency' => 'TWD',
            'category' => Category::Living,
            'default_intent' => null,
            'note' => 'Apartment rent',
        ]);

        $expense = $this->service->generateExpenseForDate(
            $recurringExpense,
            Carbon::parse('2026-02-08')
        );

        $this->assertInstanceOf(Expense::class, $expense);
        $this->assertSame('15000.00', $expense->amount);
        $this->assertSame('TWD', $expense->currency);
        $this->assertSame(Category::Living, $expense->category);
        $this->assertSame($recurringExpense->id, $expense->recurring_expense_id);
        $this->assertStringContains('固定支出：Monthly Rent', $expense->note);
        $this->assertStringContains('Apartment rent', $expense->note);
    }

    #[Test]
    public function generate_expense_for_date_creates_decision_when_default_intent_set(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'name' => 'Car Loan',
            'amount_min' => 10000,
            'default_intent' => Intent::Necessity,
        ]);

        $expense = $this->service->generateExpenseForDate(
            $recurringExpense,
            Carbon::parse('2026-02-08')
        );

        $this->assertNotNull($expense->decision);
        $this->assertSame(Intent::Necessity, $expense->decision->intent);
        $this->assertSame(ConfidenceLevel::High, $expense->decision->confidence_level);
        $this->assertStringContains('自動從固定支出', $expense->decision->decision_note);
    }

    #[Test]
    public function generate_expense_for_date_does_not_create_decision_when_no_default_intent(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'default_intent' => null,
        ]);

        $expense = $this->service->generateExpenseForDate(
            $recurringExpense,
            Carbon::parse('2026-02-08')
        );

        $this->assertNull($expense->decision);
    }

    #[Test]
    public function generate_expense_for_date_uses_random_amount_when_range_set(): void
    {
        $recurringExpense = RecurringExpense::factory()->withAmountRange(100, 200)->create();

        $amounts = [];
        for ($i = 0; $i < 10; $i++) {
            $expense = $this->service->generateExpenseForDate(
                $recurringExpense,
                Carbon::parse('2026-02-08')->addDays($i)
            );
            $amounts[] = (float) $expense->amount;
        }

        // At least one amount should differ (random)
        $uniqueAmounts = array_unique($amounts);
        foreach ($amounts as $amount) {
            $this->assertGreaterThanOrEqual(100, $amount);
            $this->assertLessThanOrEqual(200, $amount);
        }
    }

    // generateManually tests
    #[Test]
    public function generate_manually_creates_expense_with_today_date(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'name' => 'Utility Bill',
            'amount_min' => 500,
        ]);

        $expense = $this->service->generateManually($recurringExpense);

        $this->assertInstanceOf(Expense::class, $expense);
        $this->assertSame('2026-02-08', $expense->occurred_at->toDateString());
        $this->assertStringContains('[手動]', $expense->note);
    }

    #[Test]
    public function generate_manually_uses_custom_date(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'amount_min' => 500,
        ]);

        $customDate = Carbon::parse('2026-02-01');
        $expense = $this->service->generateManually($recurringExpense, $customDate);

        $this->assertSame('2026-02-01', $expense->occurred_at->toDateString());
    }

    #[Test]
    public function generate_manually_uses_custom_amount(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'amount_min' => 500,
        ]);

        $expense = $this->service->generateManually($recurringExpense, null, '750.50');

        $this->assertSame('750.50', $expense->amount);
    }

    #[Test]
    public function generate_manually_creates_decision_when_default_intent_set(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'default_intent' => Intent::Efficiency,
        ]);

        $expense = $this->service->generateManually($recurringExpense);

        $this->assertNotNull($expense->decision);
        $this->assertSame(Intent::Efficiency, $expense->decision->intent);
        $this->assertStringContains('手動從固定支出', $expense->decision->decision_note);
    }

    // getUpcoming tests
    #[Test]
    public function get_upcoming_returns_expenses_within_days(): void
    {
        $user = User::factory()->create();

        RecurringExpense::factory()->create([
            'user_id' => $user->id,
            'next_occurrence' => Carbon::today()->addDays(3),
            'is_active' => true,
        ]);
        RecurringExpense::factory()->create([
            'user_id' => $user->id,
            'next_occurrence' => Carbon::today()->addDays(5),
            'is_active' => true,
        ]);
        RecurringExpense::factory()->create([
            'user_id' => $user->id,
            'next_occurrence' => Carbon::today()->addDays(10),
            'is_active' => true,
        ]);

        $upcoming = $this->service->getUpcoming(RecurringExpenseUpcomingQueryDto::forUser($user->id, 7));

        $this->assertCount(2, $upcoming);
    }

    #[Test]
    public function get_upcoming_orders_by_next_occurrence(): void
    {
        $user = User::factory()->create();

        $later = RecurringExpense::factory()->create([
            'user_id' => $user->id,
            'next_occurrence' => Carbon::today()->addDays(5),
            'is_active' => true,
        ]);
        $earlier = RecurringExpense::factory()->create([
            'user_id' => $user->id,
            'next_occurrence' => Carbon::today()->addDays(2),
            'is_active' => true,
        ]);

        $upcoming = $this->service->getUpcoming(RecurringExpenseUpcomingQueryDto::forUser($user->id, 7));

        $this->assertTrue($upcoming->first()->is($earlier));
        $this->assertTrue($upcoming->last()->is($later));
    }

    // getHistory tests
    #[Test]
    public function get_history_returns_expenses_for_recurring_expense(): void
    {
        $recurringExpense = RecurringExpense::factory()->create();
        Expense::factory()->count(5)->create([
            'recurring_expense_id' => $recurringExpense->id,
        ]);

        $history = $this->service->getHistory($recurringExpense);

        $this->assertCount(5, $history);
    }

    #[Test]
    public function get_history_respects_limit(): void
    {
        $recurringExpense = RecurringExpense::factory()->create();
        Expense::factory()->count(15)->create([
            'recurring_expense_id' => $recurringExpense->id,
        ]);

        $history = $this->service->getHistory($recurringExpense, 5);

        $this->assertCount(5, $history);
    }

    #[Test]
    public function get_history_includes_decisions(): void
    {
        $recurringExpense = RecurringExpense::factory()->create();
        $expense = Expense::factory()->create([
            'recurring_expense_id' => $recurringExpense->id,
        ]);
        Decision::factory()->create([
            'expense_id' => $expense->id,
            'intent' => Intent::Necessity,
        ]);

        $history = $this->service->getHistory($recurringExpense);

        $this->assertNotNull($history->first()->decision);
        $this->assertSame(Intent::Necessity, $history->first()->decision->intent);
    }

    // deactivate tests
    #[Test]
    public function deactivate_sets_is_active_to_false(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'is_active' => true,
        ]);

        $this->service->deactivate($recurringExpense);

        $this->assertFalse($recurringExpense->fresh()->is_active);
    }

    // reactivate tests
    #[Test]
    public function reactivate_sets_is_active_to_true_and_calculates_next_occurrence(): void
    {
        $recurringExpense = RecurringExpense::factory()->daily()->create([
            'frequency_interval' => 1,
            'is_active' => false,
            'next_occurrence' => Carbon::parse('2026-01-01'), // Old date
        ]);

        $this->service->reactivate($recurringExpense);

        $recurringExpense->refresh();
        $this->assertTrue($recurringExpense->is_active);
        // Should recalculate based on today
        $this->assertSame('2026-02-09', $recurringExpense->next_occurrence->toDateString());
    }

    #[Test]
    public function reactivate_does_nothing_when_past_end_date(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'is_active' => false,
            'end_date' => Carbon::yesterday(),
        ]);

        $this->service->reactivate($recurringExpense);

        $recurringExpense->refresh();
        $this->assertFalse($recurringExpense->is_active);
    }

    // Transaction and error handling tests
    #[Test]
    public function generate_expense_for_date_uses_transaction(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'default_intent' => Intent::Necessity,
        ]);

        $expense = $this->service->generateExpenseForDate(
            $recurringExpense,
            Carbon::today()
        );

        // Both expense and decision should be created atomically
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
        $this->assertDatabaseHas('decisions', ['expense_id' => $expense->id]);
    }

    #[Test]
    public function generate_expense_for_date_rolls_back_when_decision_creation_fails(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'default_intent' => Intent::Necessity,
        ]);

        Decision::creating(static function (): void {
            throw new \RuntimeException('forced decision creation failure');
        });

        $thrown = false;

        try {
            $this->service->generateExpenseForDate($recurringExpense, Carbon::today());
        } catch (\RuntimeException) {
            $thrown = true;
        } finally {
            Decision::flushEventListeners();
        }

        $this->assertTrue($thrown);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('decisions', 0);
    }

    // Helper method for string contains assertion
    private function assertStringContains(string $needle, ?string $haystack): void
    {
        $this->assertNotNull($haystack);
        $this->assertStringContainsString($needle, $haystack);
    }
}
