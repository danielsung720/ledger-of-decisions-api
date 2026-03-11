<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\DTO\RecurringExpense\RecurringExpenseFiltersDto;
use App\DTO\RecurringExpense\RecurringExpensePaginateQueryDto;
use App\DTO\RecurringExpense\RecurringExpenseUpcomingQueryDto;
use App\Enums\Category;
use App\Enums\FrequencyType;
use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\User;
use App\Repositories\RecurringExpenseRepository;
use App\Support\AccessScope;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class RecurringExpenseRepositoryTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    private RecurringExpenseRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        $this->repository = new RecurringExpenseRepository;
        Carbon::setTestNow('2026-02-08 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function paginate_should_apply_scope_and_filters_and_order_by_next_occurrence(): void
    {
        $otherUser = User::factory()->create();

        $latest = RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'category' => Category::Living,
            'frequency_type' => FrequencyType::Monthly,
            'is_active' => true,
            'next_occurrence' => '2026-02-12',
        ]);
        $earliest = RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'category' => Category::Living,
            'frequency_type' => FrequencyType::Monthly,
            'is_active' => true,
            'next_occurrence' => '2026-02-10',
        ]);

        RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'category' => Category::Food,
            'frequency_type' => FrequencyType::Monthly,
            'is_active' => true,
        ]);
        RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'category' => Category::Living,
            'frequency_type' => FrequencyType::Weekly,
            'is_active' => true,
        ]);
        RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'category' => Category::Living,
            'frequency_type' => FrequencyType::Monthly,
            'is_active' => false,
        ]);
        RecurringExpense::factory()->create([
            'user_id' => $otherUser->id,
            'category' => Category::Living,
            'frequency_type' => FrequencyType::Monthly,
            'is_active' => true,
        ]);

        $result = $this->repository->paginate(
            new RecurringExpensePaginateQueryDto(
                scope: AccessScope::forUser((int) $this->user->id),
                filters: new RecurringExpenseFiltersDto(
                    categories: [Category::Living->value],
                    isActive: true,
                    frequencyTypes: [FrequencyType::Monthly->value],
                    perPage: 15
                )
            )
        );

        $this->assertSame(2, $result->total());
        $this->assertSame($earliest->id, $result->items()[0]->id);
        $this->assertSame($latest->id, $result->items()[1]->id);
    }

    #[Test]
    public function get_upcoming_should_apply_scope_and_order_by_next_occurrence(): void
    {
        $otherUser = User::factory()->create();

        $later = RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'next_occurrence' => Carbon::today()->addDays(5),
            'is_active' => true,
        ]);
        $earlier = RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'next_occurrence' => Carbon::today()->addDays(2),
            'is_active' => true,
        ]);

        RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
            'next_occurrence' => Carbon::today()->addDays(10),
            'is_active' => true,
        ]);
        RecurringExpense::factory()->create([
            'user_id' => $otherUser->id,
            'next_occurrence' => Carbon::today()->addDays(3),
            'is_active' => true,
        ]);

        $upcoming = $this->repository->getUpcoming(
            RecurringExpenseUpcomingQueryDto::forUser((int) $this->user->id, 7)
        );

        $this->assertCount(2, $upcoming);
        $this->assertTrue($upcoming->first()->is($earlier));
        $this->assertTrue($upcoming->last()->is($later));
    }

    #[Test]
    public function get_history_should_order_by_occurred_at_desc_and_respect_limit(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $latest = Expense::factory()->create([
            'user_id' => $this->user->id,
            'recurring_expense_id' => $recurringExpense->id,
            'occurred_at' => '2026-02-07 10:00:00',
        ]);
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'recurring_expense_id' => $recurringExpense->id,
            'occurred_at' => '2026-02-06 10:00:00',
        ]);
        $oldest = Expense::factory()->create([
            'user_id' => $this->user->id,
            'recurring_expense_id' => $recurringExpense->id,
            'occurred_at' => '2026-02-05 10:00:00',
        ]);

        $history = $this->repository->getHistory($recurringExpense, 2);

        $this->assertCount(2, $history);
        $this->assertSame($latest->id, $history->first()->id);
        $this->assertNotSame($oldest->id, $history->last()->id);
    }
}
