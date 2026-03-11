<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\DTO\Statistics\StatisticsFilterDto;
use App\DTO\Statistics\StatisticsQueryDto;
use App\DTO\Statistics\TrendsStatisticsQueryDto;
use App\Enums\ConfidenceLevel;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use App\Models\User;
use App\Repositories\ExpenseRepository;
use App\Support\AccessScope;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ExpenseRepository();
    }

    #[Test]
    public function GetIntentStatisticsForUserShouldApplyUserScopeAndPresetFilter(): void
    {
        Carbon::setTestNow('2026-02-14 12:00:00');

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $todayExpense = Expense::factory()->create(['user_id' => $user->id]);
        $weekExpense = Expense::factory()->create(['user_id' => $user->id]);
        $monthExpense = Expense::factory()->create(['user_id' => $user->id]);
        $otherUserExpense = Expense::factory()->create(['user_id' => $otherUser->id]);

        Decision::factory()->create([
            'expense_id' => $todayExpense->id,
            'intent' => Intent::Necessity,
            'confidence_level' => ConfidenceLevel::High,
            'created_at' => '2026-02-14 09:00:00',
        ]);
        Decision::factory()->create([
            'expense_id' => $weekExpense->id,
            'intent' => Intent::Necessity,
            'confidence_level' => ConfidenceLevel::Medium,
            'created_at' => '2026-02-10 09:00:00',
        ]);
        Decision::factory()->create([
            'expense_id' => $monthExpense->id,
            'intent' => Intent::Necessity,
            'confidence_level' => ConfidenceLevel::Low,
            'created_at' => '2026-02-03 09:00:00',
        ]);
        Decision::factory()->create([
            'expense_id' => $otherUserExpense->id,
            'intent' => Intent::Necessity,
            'confidence_level' => ConfidenceLevel::High,
            'created_at' => '2026-02-14 09:00:00',
        ]);

        $stats = $this->repository->getIntentStatistics(
            StatisticsQueryDto::forUser($user->id, StatisticsFilterDto::fromArray(['preset' => 'this_week']))
        );
        $necessity = $stats->firstWhere('intent', Intent::Necessity->value);

        $this->assertNotNull($necessity);
        $this->assertSame(2, $necessity->count);
        $this->assertEquals(2.5, $necessity->avgConfidenceScore);

        Carbon::setTestNow();
    }

    #[Test]
    public function GetSummaryByCategoryForUserShouldApplyOccurredAtRangeInclusive(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Expense::factory()->create([
            'user_id' => $user->id,
            'category' => 'food',
            'amount' => 100,
            'occurred_at' => '2026-02-10 00:00:00',
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category' => 'transport',
            'amount' => 200,
            'occurred_at' => '2026-02-12 23:59:59',
        ]);
        Expense::factory()->create([
            'user_id' => $user->id,
            'category' => 'food',
            'amount' => 999,
            'occurred_at' => '2026-02-13 00:00:00',
        ]);
        Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category' => 'food',
            'amount' => 888,
            'occurred_at' => '2026-02-11 00:00:00',
        ]);

        $stats = $this->repository->getSummaryByCategory(
            StatisticsQueryDto::forUser($user->id, StatisticsFilterDto::fromArray([
                'start_date' => '2026-02-10',
                'end_date' => '2026-02-12',
            ]))
        );

        $byCategory = $stats->mapWithKeys(function ($row): array {
            return [$row->category => $row->totalAmount];
        });

        $this->assertCount(2, $stats);
        $this->assertEquals(100.0, $byCategory['food']);
        $this->assertEquals(200.0, $byCategory['transport']);
    }

    #[Test]
    public function GetSummaryByIntentForUserShouldApplyOccurredAtRangeAndGroupCorrectly(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $e1 = Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 50,
            'occurred_at' => '2026-02-10 00:00:00',
        ]);
        $e2 = Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 70,
            'occurred_at' => '2026-02-12 23:59:59',
        ]);
        $e3 = Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 80,
            'occurred_at' => '2026-02-13 00:00:00',
        ]);
        $e4 = Expense::factory()->create([
            'user_id' => $otherUser->id,
            'amount' => 90,
            'occurred_at' => '2026-02-11 00:00:00',
        ]);

        Decision::factory()->create(['expense_id' => $e1->id, 'intent' => Intent::Necessity]);
        Decision::factory()->create(['expense_id' => $e2->id, 'intent' => Intent::Impulse]);
        Decision::factory()->create(['expense_id' => $e3->id, 'intent' => Intent::Necessity]);
        Decision::factory()->create(['expense_id' => $e4->id, 'intent' => Intent::Necessity]);

        $stats = $this->repository->getSummaryByIntent(
            StatisticsQueryDto::forUser($user->id, StatisticsFilterDto::fromArray([
                'start_date' => '2026-02-10',
                'end_date' => '2026-02-12',
            ]))
        );

        $byIntent = $stats->keyBy('intent');

        $this->assertCount(2, $stats);
        $this->assertEquals(50.0, $byIntent[Intent::Necessity->value]->totalAmount);
        $this->assertSame(1, $byIntent[Intent::Necessity->value]->count);
        $this->assertEquals(70.0, $byIntent[Intent::Impulse->value]->totalAmount);
        $this->assertSame(1, $byIntent[Intent::Impulse->value]->count);
    }

    #[Test]
    public function CountSumAndImpulseCountForUserShouldApplyFiltersAndUserScope(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $inRangeImpulse = Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 100,
            'occurred_at' => '2026-02-10 10:00:00',
        ]);
        $inRangeNecessity = Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 50,
            'occurred_at' => '2026-02-11 10:00:00',
        ]);
        $outRangeImpulse = Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 500,
            'occurred_at' => '2026-02-13 10:00:00',
        ]);
        $otherUserInRange = Expense::factory()->create([
            'user_id' => $otherUser->id,
            'amount' => 999,
            'occurred_at' => '2026-02-11 10:00:00',
        ]);

        Decision::factory()->create(['expense_id' => $inRangeImpulse->id, 'intent' => Intent::Impulse]);
        Decision::factory()->create(['expense_id' => $inRangeNecessity->id, 'intent' => Intent::Necessity]);
        Decision::factory()->create(['expense_id' => $outRangeImpulse->id, 'intent' => Intent::Impulse]);
        Decision::factory()->create(['expense_id' => $otherUserInRange->id, 'intent' => Intent::Impulse]);

        $filters = [
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-12',
        ];

        $totals = $this->repository->getSummaryTotals(
            StatisticsQueryDto::forUser($user->id, StatisticsFilterDto::fromArray($filters))
        );

        $this->assertSame(2, $totals->totalCount);
        $this->assertEquals(150.0, $totals->totalAmount);
        $this->assertSame(1, $totals->impulseCount);
    }

    #[Test]
    public function SumImpulseSpendingForUserBetweenShouldFilterByDateAndUser(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $inRangeImpulse = Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 80,
            'occurred_at' => '2026-02-10 10:00:00',
        ]);
        $inRangeNecessity = Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 20,
            'occurred_at' => '2026-02-11 10:00:00',
        ]);
        $outRangeImpulse = Expense::factory()->create([
            'user_id' => $user->id,
            'amount' => 100,
            'occurred_at' => '2026-02-13 10:00:00',
        ]);
        $otherUserInRangeImpulse = Expense::factory()->create([
            'user_id' => $otherUser->id,
            'amount' => 500,
            'occurred_at' => '2026-02-11 10:00:00',
        ]);

        Decision::factory()->create(['expense_id' => $inRangeImpulse->id, 'intent' => Intent::Impulse]);
        Decision::factory()->create(['expense_id' => $inRangeNecessity->id, 'intent' => Intent::Necessity]);
        Decision::factory()->create(['expense_id' => $outRangeImpulse->id, 'intent' => Intent::Impulse]);
        Decision::factory()->create(['expense_id' => $otherUserInRangeImpulse->id, 'intent' => Intent::Impulse]);

        $sum = $this->repository->getTrendsImpulseComparison(
            new TrendsStatisticsQueryDto(
                scope: AccessScope::forUser($user->id),
                thisWeekStart: Carbon::parse('2026-02-10 00:00:00'),
                thisWeekEnd: Carbon::parse('2026-02-12 23:59:59'),
                lastWeekStart: Carbon::parse('2026-02-01 00:00:00'),
                lastWeekEnd: Carbon::parse('2026-02-02 23:59:59'),
            )
        );

        $this->assertEquals(80.0, $sum->thisWeek);
    }

    #[Test]
    public function GetTopHighConfidenceIntentsForUserShouldReturnDescOrderWithLimit(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        foreach (range(1, 3) as $_) {
            $expense = Expense::factory()->create(['user_id' => $user->id]);
            Decision::factory()->create([
                'expense_id' => $expense->id,
                'intent' => Intent::Necessity,
                'confidence_level' => ConfidenceLevel::High,
            ]);
        }

        foreach (range(1, 2) as $_) {
            $expense = Expense::factory()->create(['user_id' => $user->id]);
            Decision::factory()->create([
                'expense_id' => $expense->id,
                'intent' => Intent::Impulse,
                'confidence_level' => ConfidenceLevel::High,
            ]);
        }

        $expense = Expense::factory()->create(['user_id' => $user->id]);
        Decision::factory()->create([
            'expense_id' => $expense->id,
            'intent' => Intent::Efficiency,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        $expense = Expense::factory()->create(['user_id' => $otherUser->id]);
        Decision::factory()->create([
            'expense_id' => $expense->id,
            'intent' => Intent::Efficiency,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        $stats = $this->repository->getTopHighConfidenceIntents(
            new TrendsStatisticsQueryDto(
                scope: AccessScope::forUser($user->id),
                thisWeekStart: Carbon::parse('2026-02-10 00:00:00'),
                thisWeekEnd: Carbon::parse('2026-02-12 23:59:59'),
                lastWeekStart: Carbon::parse('2026-02-01 00:00:00'),
                lastWeekEnd: Carbon::parse('2026-02-02 23:59:59'),
                highConfidenceLimit: 2,
            )
        );

        $this->assertCount(2, $stats);
        $topIntent = $stats[0]->intent;
        $secondIntent = $stats[1]->intent;
        $this->assertSame(Intent::Necessity->value, $topIntent);
        $this->assertSame(3, $stats[0]->count);
        $this->assertSame(Intent::Impulse->value, $secondIntent);
        $this->assertSame(2, $stats[1]->count);
    }

    #[Test]
    public function GetSummaryByCategoryShouldSupportMultiUserScope(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userC = User::factory()->create();

        Expense::factory()->create([
            'user_id' => $userA->id,
            'category' => 'food',
            'amount' => 100,
            'occurred_at' => '2026-02-11 10:00:00',
        ]);

        Expense::factory()->create([
            'user_id' => $userB->id,
            'category' => 'food',
            'amount' => 200,
            'occurred_at' => '2026-02-11 11:00:00',
        ]);

        Expense::factory()->create([
            'user_id' => $userC->id,
            'category' => 'food',
            'amount' => 999,
            'occurred_at' => '2026-02-11 12:00:00',
        ]);

        $scope = AccessScope::forUsers([$userA->id, $userB->id]);
        $stats = $this->repository->getSummaryByCategory(
            new StatisticsQueryDto(
                scope: $scope,
                filter: StatisticsFilterDto::fromArray([
                    'start_date' => '2026-02-10',
                    'end_date' => '2026-02-12',
                ]),
            )
        );

        $food = $stats->firstWhere('category', 'food');

        $this->assertNotNull($food);
        $this->assertEquals(300.0, $food->totalAmount);
        $this->assertSame(2, $food->count);
    }
}
