<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Category;
use App\Enums\ConfidenceLevel;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class StatisticsApiTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
    }

    #[Test]
    public function IntentsStatisticsShouldReturnExpectedData(): void
    {
        $expense1 = Expense::factory()->create(['user_id' => $this->user->id]);
        $expense2 = Expense::factory()->create(['user_id' => $this->user->id]);
        $expense3 = Expense::factory()->create(['user_id' => $this->user->id]);

        Decision::factory()->create(['expense_id' => $expense1->id, 'intent' => Intent::Necessity, 'confidence_level' => ConfidenceLevel::High]);
        Decision::factory()->create(['expense_id' => $expense2->id, 'intent' => Intent::Necessity, 'confidence_level' => ConfidenceLevel::Medium]);
        Decision::factory()->create(['expense_id' => $expense3->id, 'intent' => Intent::Impulse, 'confidence_level' => ConfidenceLevel::Low]);
        $this->createOtherUserExpenseWithDecision(Intent::Necessity, ConfidenceLevel::High, 999);

        $response = $this->getJson('/api/statistics/intents');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['intent', 'intent_label', 'count', 'avg_confidence_score', 'avg_confidence_level'],
                ],
            ]);

        $data = $response->json('data');
        $necessityStats = collect($data)->firstWhere('intent', 'necessity');
        $this->assertEquals(2, $necessityStats['count']);
    }

    #[Test]
    public function SummaryStatisticsShouldReturnExpectedData(): void
    {
        $expense1 = Expense::factory()->create(['user_id' => $this->user->id, 'amount' => 100, 'category' => Category::Food]);
        $expense2 = Expense::factory()->create(['user_id' => $this->user->id, 'amount' => 200, 'category' => Category::Food]);
        $expense3 = Expense::factory()->create(['user_id' => $this->user->id, 'amount' => 150, 'category' => Category::Transport]);

        Decision::factory()->create(['expense_id' => $expense1->id, 'intent' => Intent::Necessity]);
        Decision::factory()->create(['expense_id' => $expense2->id, 'intent' => Intent::Impulse]);
        Decision::factory()->create(['expense_id' => $expense3->id, 'intent' => Intent::Efficiency]);
        $this->createOtherUserExpenseWithDecision(Intent::Impulse, ConfidenceLevel::High, 9999);

        $response = $this->getJson('/api/statistics/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_amount' => 450,
                    'total_count' => 3,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_amount',
                    'total_count',
                    'by_category',
                    'by_intent',
                    'impulse_spending_ratio',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(450, $data['total_amount']);
        $this->assertEqualsWithDelta(33.33, $data['impulse_spending_ratio'], 0.01);
    }

    #[Test]
    public function TrendsStatisticsShouldReturnExpectedData(): void
    {
        // Create expenses for this week and last week
        $thisWeek = now()->startOfWeek()->addDays(2);
        $lastWeek = now()->subWeek()->startOfWeek()->addDays(2);

        $expense1 = Expense::factory()->create(['user_id' => $this->user->id, 'amount' => 100, 'occurred_at' => $thisWeek]);
        $expense2 = Expense::factory()->create(['user_id' => $this->user->id, 'amount' => 50, 'occurred_at' => $lastWeek]);

        Decision::factory()->create(['expense_id' => $expense1->id, 'intent' => Intent::Impulse, 'confidence_level' => ConfidenceLevel::High]);
        Decision::factory()->create(['expense_id' => $expense2->id, 'intent' => Intent::Impulse, 'confidence_level' => ConfidenceLevel::High]);
        $otherUser = User::factory()->create();
        $otherExpense = Expense::factory()->create([
            'user_id' => $otherUser->id,
            'amount' => 300,
            'occurred_at' => $thisWeek,
        ]);
        Decision::factory()->create([
            'expense_id' => $otherExpense->id,
            'intent' => Intent::Impulse,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        $response = $this->getJson('/api/statistics/trends');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'impulse_spending' => ['this_week', 'last_week', 'change_percentage', 'trend'],
                    'high_confidence_intents',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(100, $data['impulse_spending']['this_week']);
        $this->assertEquals(50, $data['impulse_spending']['last_week']);
        $this->assertEquals('up', $data['impulse_spending']['trend']);
    }

    #[Test]
    public function SummaryShouldReturnZeroWhenNoData(): void
    {
        $response = $this->getJson('/api/statistics/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_amount' => 0,
                    'total_count' => 0,
                    'impulse_spending_ratio' => 0,
                ],
            ]);
    }

    #[Test]
    public function StatisticsEndpointsShouldRequireAuthentication(): void
    {
        auth()->logout();

        $this->getJson('/api/statistics/intents')->assertStatus(401);
        $this->getJson('/api/statistics/summary')->assertStatus(401);
        $this->getJson('/api/statistics/trends')->assertStatus(401);
    }

    #[Test]
    public function IntentsShouldReturn422WhenPresetIsInvalid(): void
    {
        $response = $this->getJson('/api/statistics/intents?preset=invalid');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'success',
                'error',
                'errors' => ['preset'],
            ]);
    }

    #[Test]
    public function SummaryShouldReturn422WhenStartDateIsAfterEndDate(): void
    {
        $response = $this->getJson('/api/statistics/summary?start_date=2026-02-12&end_date=2026-02-10');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'success',
                'error',
                'errors' => ['start_date'],
            ]);
    }

    #[Test]
    public function IntentsDateRangeShouldIncludeStartAndEndBoundaries(): void
    {
        $expense1 = Expense::factory()->create(['user_id' => $this->user->id]);
        $expense2 = Expense::factory()->create(['user_id' => $this->user->id]);
        $expense3 = Expense::factory()->create(['user_id' => $this->user->id]);

        Decision::factory()->create([
            'expense_id' => $expense1->id,
            'intent' => Intent::Necessity,
            'created_at' => '2026-02-10 00:00:00',
        ]);
        Decision::factory()->create([
            'expense_id' => $expense2->id,
            'intent' => Intent::Necessity,
            'created_at' => '2026-02-12 23:59:59',
        ]);
        Decision::factory()->create([
            'expense_id' => $expense3->id,
            'intent' => Intent::Impulse,
            'created_at' => '2026-02-13 00:00:00',
        ]);

        $response = $this->getJson('/api/statistics/intents?start_date=2026-02-10&end_date=2026-02-12');
        $response->assertStatus(200);

        $data = collect($response->json('data'));
        $necessityStats = $data->firstWhere('intent', Intent::Necessity->value);
        $impulseStats = $data->firstWhere('intent', Intent::Impulse->value);

        $this->assertSame(2, $necessityStats['count']);
        $this->assertNull($impulseStats);
    }

    #[Test]
    public function SummaryDateRangeShouldIncludeStartAndEndBoundaries(): void
    {
        $expense1 = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100,
            'created_at' => '2026-02-10 00:00:00',
            'occurred_at' => '2026-02-10 00:00:00',
        ]);
        $expense2 = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 200,
            'created_at' => '2026-02-12 23:59:59',
            'occurred_at' => '2026-02-12 23:59:59',
        ]);
        $expense3 = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 500,
            'created_at' => '2026-02-13 00:00:00',
            'occurred_at' => '2026-02-13 00:00:00',
        ]);

        Decision::factory()->create(['expense_id' => $expense1->id, 'intent' => Intent::Necessity]);
        Decision::factory()->create(['expense_id' => $expense2->id, 'intent' => Intent::Efficiency]);
        Decision::factory()->create(['expense_id' => $expense3->id, 'intent' => Intent::Impulse]);

        $response = $this->getJson('/api/statistics/summary?start_date=2026-02-10&end_date=2026-02-12');
        $response->assertStatus(200);

        $this->assertEquals(300.0, $response->json('data.total_amount'));
        $this->assertSame(2, $response->json('data.total_count'));
    }

    #[Test]
    public function TrendsChangePercentageShouldBe100WhenLastWeekIsZeroAndThisWeekHasSpending(): void
    {
        Carbon::setTestNow('2026-02-14 12:00:00');

        $thisWeekExpense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 120,
            'occurred_at' => Carbon::now()->startOfWeek()->addDays(1),
        ]);
        Decision::factory()->create([
            'expense_id' => $thisWeekExpense->id,
            'intent' => Intent::Impulse,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        $response = $this->getJson('/api/statistics/trends');
        $response->assertStatus(200);

        $this->assertSame(100, $response->json('data.impulse_spending.change_percentage'));
        $this->assertSame('up', $response->json('data.impulse_spending.trend'));

        Carbon::setTestNow();
    }

    #[Test]
    public function TrendsChangePercentageShouldBeNegativeWhenThisWeekIsLessThanLastWeek(): void
    {
        Carbon::setTestNow('2026-02-14 12:00:00');

        $lastWeekExpense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 80,
            'occurred_at' => Carbon::now()->subWeek()->startOfWeek()->addDays(2),
        ]);
        Decision::factory()->create([
            'expense_id' => $lastWeekExpense->id,
            'intent' => Intent::Impulse,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        $response = $this->getJson('/api/statistics/trends');
        $response->assertStatus(200);

        $this->assertEquals(-100.0, $response->json('data.impulse_spending.change_percentage'));
        $this->assertSame('down', $response->json('data.impulse_spending.trend'));

        Carbon::setTestNow();
    }

    #[Test]
    public function IntentsPresetShouldFilterTodayThisWeekThisMonth(): void
    {
        Carbon::setTestNow('2026-02-14 12:00:00');

        $todayExpense = Expense::factory()->create(['user_id' => $this->user->id]);
        $thisWeekExpense = Expense::factory()->create(['user_id' => $this->user->id]);
        $thisMonthExpense = Expense::factory()->create(['user_id' => $this->user->id]);
        $lastMonthExpense = Expense::factory()->create(['user_id' => $this->user->id]);

        Decision::factory()->create([
            'expense_id' => $todayExpense->id,
            'intent' => Intent::Necessity,
            'created_at' => '2026-02-14 10:00:00',
        ]);
        Decision::factory()->create([
            'expense_id' => $thisWeekExpense->id,
            'intent' => Intent::Necessity,
            'created_at' => '2026-02-10 10:00:00',
        ]);
        Decision::factory()->create([
            'expense_id' => $thisMonthExpense->id,
            'intent' => Intent::Necessity,
            'created_at' => '2026-02-03 10:00:00',
        ]);
        Decision::factory()->create([
            'expense_id' => $lastMonthExpense->id,
            'intent' => Intent::Necessity,
            'created_at' => '2026-01-31 10:00:00',
        ]);

        $todayCount = collect($this->getJson('/api/statistics/intents?preset=today')->json('data'))
            ->firstWhere('intent', Intent::Necessity->value)['count'] ?? 0;
        $thisWeekCount = collect($this->getJson('/api/statistics/intents?preset=this_week')->json('data'))
            ->firstWhere('intent', Intent::Necessity->value)['count'] ?? 0;
        $thisMonthCount = collect($this->getJson('/api/statistics/intents?preset=this_month')->json('data'))
            ->firstWhere('intent', Intent::Necessity->value)['count'] ?? 0;

        $this->assertSame(1, $todayCount);
        $this->assertSame(2, $thisWeekCount);
        $this->assertSame(3, $thisMonthCount);

        Carbon::setTestNow();
    }

    #[Test]
    public function SummaryPresetShouldFilterTodayThisWeekThisMonth(): void
    {
        Carbon::setTestNow('2026-02-14 12:00:00');

        $todayExpense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 10,
            'created_at' => '2026-02-14 10:00:00',
            'occurred_at' => '2026-02-14 10:00:00',
        ]);
        $thisWeekExpense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 20,
            'created_at' => '2026-02-10 10:00:00',
            'occurred_at' => '2026-02-10 10:00:00',
        ]);
        $thisMonthExpense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 30,
            'created_at' => '2026-02-03 10:00:00',
            'occurred_at' => '2026-02-03 10:00:00',
        ]);
        $lastMonthExpense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 40,
            'created_at' => '2026-01-31 10:00:00',
            'occurred_at' => '2026-01-31 10:00:00',
        ]);

        Decision::factory()->create(['expense_id' => $todayExpense->id, 'intent' => Intent::Necessity]);
        Decision::factory()->create(['expense_id' => $thisWeekExpense->id, 'intent' => Intent::Necessity]);
        Decision::factory()->create(['expense_id' => $thisMonthExpense->id, 'intent' => Intent::Necessity]);
        Decision::factory()->create(['expense_id' => $lastMonthExpense->id, 'intent' => Intent::Necessity]);

        $todaySummary = $this->getJson('/api/statistics/summary?preset=today')->json('data');
        $thisWeekSummary = $this->getJson('/api/statistics/summary?preset=this_week')->json('data');
        $thisMonthSummary = $this->getJson('/api/statistics/summary?preset=this_month')->json('data');

        $this->assertSame(1, $todaySummary['total_count']);
        $this->assertEquals(10.0, $todaySummary['total_amount']);
        $this->assertSame(2, $thisWeekSummary['total_count']);
        $this->assertEquals(30.0, $thisWeekSummary['total_amount']);
        $this->assertSame(3, $thisMonthSummary['total_count']);
        $this->assertEquals(60.0, $thisMonthSummary['total_amount']);

        Carbon::setTestNow();
    }

    #[Test]
    public function TrendsShouldBeStableWhenThisWeekEqualsLastWeek(): void
    {
        Carbon::setTestNow('2026-02-14 12:00:00');

        $thisWeekExpense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 50,
            'occurred_at' => Carbon::now()->startOfWeek()->addDays(1),
        ]);
        $lastWeekExpense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 50,
            'occurred_at' => Carbon::now()->subWeek()->startOfWeek()->addDays(1),
        ]);

        Decision::factory()->create([
            'expense_id' => $thisWeekExpense->id,
            'intent' => Intent::Impulse,
            'confidence_level' => ConfidenceLevel::High,
        ]);
        Decision::factory()->create([
            'expense_id' => $lastWeekExpense->id,
            'intent' => Intent::Impulse,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        $response = $this->getJson('/api/statistics/trends');
        $response->assertStatus(200);

        $this->assertEquals(0.0, $response->json('data.impulse_spending.change_percentage'));
        $this->assertSame('stable', $response->json('data.impulse_spending.trend'));

        Carbon::setTestNow();
    }

    #[Test]
    public function PresetShouldTakePrecedenceOverStartAndEndDateFilters(): void
    {
        Carbon::setTestNow('2026-02-14 12:00:00');

        $todayExpense = Expense::factory()->create(['user_id' => $this->user->id]);
        $olderExpense = Expense::factory()->create(['user_id' => $this->user->id]);

        Decision::factory()->create([
            'expense_id' => $todayExpense->id,
            'intent' => Intent::Necessity,
            'created_at' => '2026-02-14 09:00:00',
        ]);
        Decision::factory()->create([
            'expense_id' => $olderExpense->id,
            'intent' => Intent::Necessity,
            'created_at' => '2026-02-10 09:00:00',
        ]);

        $response = $this->getJson('/api/statistics/intents?preset=today&start_date=2026-02-10&end_date=2026-02-12');
        $response->assertStatus(200);

        $necessityCount = collect($response->json('data'))->firstWhere('intent', Intent::Necessity->value)['count'] ?? 0;
        $this->assertSame(1, $necessityCount);

        Carbon::setTestNow();
    }

    private function createOtherUserExpenseWithDecision(Intent $intent, ConfidenceLevel $confidenceLevel, int $amount): void
    {
        $otherUser = User::factory()->create();
        $expense = Expense::factory()->create([
            'user_id' => $otherUser->id,
            'amount' => $amount,
        ]);

        Decision::factory()->create([
            'expense_id' => $expense->id,
            'intent' => $intent,
            'confidence_level' => $confidenceLevel,
        ]);
    }
}
