<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\DTO\Expense\BatchDeleteExpenseDto;
use App\DTO\Expense\CreateExpenseDto;
use App\DTO\Expense\ExpenseBatchDeleteQueryDto;
use App\DTO\Expense\ExpenseFiltersDto;
use App\DTO\Expense\ExpensePaginateQueryDto;
use App\DTO\Expense\UpdateExpenseDto;
use App\Enums\Category;
use App\Enums\ConfidenceLevel;
use App\Enums\DatePreset;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use App\Models\User;
use App\Repositories\ExpenseCrudRepository;
use App\Support\AccessScope;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class ExpenseCrudRepositoryTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    private ExpenseCrudRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        $this->repository = new ExpenseCrudRepository();
        Carbon::setTestNow('2026-02-14 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function PaginateShouldApplyTodayPreset(): void
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'occurred_at' => '2026-02-14 09:00:00',
        ]);
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'occurred_at' => '2026-02-13 09:00:00',
        ]);

        $result = $this->repository->paginate(
            new ExpensePaginateQueryDto(
                scope: AccessScope::forUser((int) $this->user->id),
                filters: new ExpenseFiltersDto(
                    startDate: null,
                    endDate: null,
                    preset: DatePreset::Today,
                    categories: [],
                    intents: [],
                    confidenceLevels: [],
                    perPage: 15,
                )
            )
        );

        $this->assertSame(1, $result->total());
    }

    #[Test]
    public function PaginateShouldApplyThisWeekPreset(): void
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'occurred_at' => '2026-02-10 09:00:00',
        ]);
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'occurred_at' => '2026-02-01 09:00:00',
        ]);

        $result = $this->repository->paginate(
            new ExpensePaginateQueryDto(
                scope: AccessScope::forUser((int) $this->user->id),
                filters: new ExpenseFiltersDto(
                    startDate: null,
                    endDate: null,
                    preset: DatePreset::ThisWeek,
                    categories: [],
                    intents: [],
                    confidenceLevels: [],
                    perPage: 15,
                )
            )
        );

        $this->assertSame(1, $result->total());
    }

    #[Test]
    public function PaginateShouldApplyThisMonthPresetAndCompositeFilters(): void
    {
        $match = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => Category::Food,
            'occurred_at' => '2026-02-10 09:00:00',
        ]);
        Decision::factory()->create([
            'expense_id' => $match->id,
            'intent' => Intent::Necessity,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        $wrongCategory = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => Category::Transport,
            'occurred_at' => '2026-02-10 09:00:00',
        ]);
        Decision::factory()->create([
            'expense_id' => $wrongCategory->id,
            'intent' => Intent::Necessity,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        $wrongIntent = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => Category::Food,
            'occurred_at' => '2026-02-10 09:00:00',
        ]);
        Decision::factory()->create([
            'expense_id' => $wrongIntent->id,
            'intent' => Intent::Impulse,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => Category::Food,
            'occurred_at' => '2026-01-20 09:00:00',
        ]);

        $result = $this->repository->paginate(
            new ExpensePaginateQueryDto(
                scope: AccessScope::forUser((int) $this->user->id),
                filters: new ExpenseFiltersDto(
                    startDate: '2026-02-01',
                    endDate: '2026-02-28',
                    preset: DatePreset::ThisMonth,
                    categories: [Category::Food->value],
                    intents: [Intent::Necessity->value],
                    confidenceLevels: [ConfidenceLevel::High->value],
                    perPage: 15,
                )
            )
        );

        $this->assertSame(1, $result->total());
        $this->assertSame($match->id, $result->items()[0]->id);
    }

    #[Test]
    public function PresetShouldTakePrecedenceOverStartAndEndDateFilters(): void
    {
        $today = Expense::factory()->create([
            'user_id' => $this->user->id,
            'occurred_at' => '2026-02-14 09:00:00',
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'occurred_at' => '2026-01-10 09:00:00',
        ]);

        $result = $this->repository->paginate(
            new ExpensePaginateQueryDto(
                scope: AccessScope::forUser((int) $this->user->id),
                filters: new ExpenseFiltersDto(
                    startDate: '2026-01-01',
                    endDate: '2026-01-31',
                    preset: DatePreset::Today,
                    categories: [],
                    intents: [],
                    confidenceLevels: [],
                    perPage: 15,
                )
            )
        );

        $this->assertSame(1, $result->total());
        $this->assertSame($today->id, $result->items()[0]->id);
    }

    #[Test]
    public function CreateShouldPersistExpense(): void
    {
        $expense = $this->repository->create(
            CreateExpenseDto::fromArray([
                'amount' => 350,
                'category' => Category::Living->value,
                'occurred_at' => '2026-02-14 08:00:00',
                'note' => 'rent',
            ])
        );

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'user_id' => $this->user->id,
            'amount' => 350,
            'category' => Category::Living->value,
        ]);
    }

    #[Test]
    public function ShowShouldLoadDecisionRelation(): void
    {
        $expense = Expense::factory()->create(['user_id' => $this->user->id]);
        Decision::factory()->create(['expense_id' => $expense->id]);

        $result = $this->repository->show($expense);

        $this->assertTrue($result->relationLoaded('decision'));
        $this->assertNotNull($result->decision);
    }

    #[Test]
    public function UpdateShouldModifyExpenseAndKeepDecisionLoaded(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100,
            'note' => 'old',
        ]);
        Decision::factory()->create(['expense_id' => $expense->id]);

        $updated = $this->repository->update(
            $expense,
            UpdateExpenseDto::fromArray([
                'amount' => 200,
                'note' => null,
            ])
        );

        $this->assertSame('200.00', $updated->amount);
        $this->assertNull($updated->note);
        $this->assertTrue($updated->relationLoaded('decision'));
    }

    #[Test]
    public function DeleteShouldRemoveExpense(): void
    {
        $expense = Expense::factory()->create(['user_id' => $this->user->id]);

        $this->repository->delete($expense);

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    #[Test]
    public function BatchDeleteShouldDeleteOnlyScopedRecords(): void
    {
        $otherUser = User::factory()->create();

        $mineA = Expense::factory()->create(['user_id' => $this->user->id]);
        $mineB = Expense::factory()->create(['user_id' => $this->user->id]);
        $other = Expense::factory()->create(['user_id' => $otherUser->id]);

        $deleted = $this->repository->batchDelete(
            new ExpenseBatchDeleteQueryDto(
                scope: AccessScope::forUser((int) $this->user->id),
                payload: new BatchDeleteExpenseDto([$mineA->id, $mineB->id, $other->id])
            )
        );

        $this->assertSame(2, $deleted);
        $this->assertDatabaseMissing('expenses', ['id' => $mineA->id]);
        $this->assertDatabaseMissing('expenses', ['id' => $mineB->id]);
        $this->assertDatabaseHas('expenses', ['id' => $other->id]);
    }
}
