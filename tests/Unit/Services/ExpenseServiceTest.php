<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Expense\BatchDeleteExpenseDto;
use App\DTO\Expense\CreateExpenseDto;
use App\DTO\Expense\ExpenseBatchDeleteQueryDto;
use App\DTO\Expense\ExpenseFiltersDto;
use App\DTO\Expense\ExpensePaginateQueryDto;
use App\DTO\Expense\UpdateExpenseDto;
use App\Enums\Category;
use App\Enums\ConfidenceLevel;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use App\Models\User;
use App\Repositories\ExpenseCrudRepository;
use App\Services\ExpenseService;
use App\Support\AccessScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class ExpenseServiceTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    private ExpenseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        $this->service = new ExpenseService(new ExpenseCrudRepository());
    }

    #[Test]
    public function PaginateShouldFilterByAccessScopeAndConditions(): void
    {
        $otherUser = User::factory()->create();

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

        $noIntent = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category' => Category::Food,
            'occurred_at' => '2026-02-12 09:00:00',
        ]);
        Decision::factory()->create([
            'expense_id' => $noIntent->id,
            'intent' => Intent::Impulse,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category' => Category::Food,
            'occurred_at' => '2026-02-10 10:00:00',
        ]);

        $result = $this->service->paginate(
            new ExpensePaginateQueryDto(
                scope: AccessScope::forUser((int) $this->user->id),
                filters: new ExpenseFiltersDto(
                    startDate: '2026-02-01',
                    endDate: '2026-02-28',
                    preset: null,
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
    public function CreateShouldPersistExpenseForAuthenticatedUser(): void
    {
        $expense = $this->service->create(
            CreateExpenseDto::fromArray([
                'amount' => 300,
                'category' => Category::Transport->value,
                'occurred_at' => '2026-02-01 08:00:00',
                'note' => 'Taxi',
            ])
        );

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'user_id' => $this->user->id,
            'amount' => 300,
            'category' => Category::Transport->value,
        ]);
    }

    #[Test]
    public function ShowShouldReturnExpenseWithDecisionLoaded(): void
    {
        $expense = Expense::factory()->create(['user_id' => $this->user->id]);
        Decision::factory()->create(['expense_id' => $expense->id]);

        $result = $this->service->show($expense);

        $this->assertTrue($result->relationLoaded('decision'));
        $this->assertNotNull($result->decision);
    }

    #[Test]
    public function UpdateShouldModifyExpenseFields(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100,
            'note' => 'old',
        ]);

        $updated = $this->service->update(
            $expense,
            UpdateExpenseDto::fromArray([
                'amount' => 200,
                'note' => null,
            ])
        );

        $this->assertSame('200.00', $updated->amount);
        $this->assertNull($updated->note);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'amount' => 200,
            'note' => null,
        ]);
    }

    #[Test]
    public function DeleteShouldRemoveExpense(): void
    {
        $expense = Expense::factory()->create(['user_id' => $this->user->id]);

        $this->service->delete($expense);

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    #[Test]
    public function BatchDeleteShouldOnlyDeleteScopedRecords(): void
    {
        $otherUser = User::factory()->create();
        $mineA = Expense::factory()->create(['user_id' => $this->user->id]);
        $mineB = Expense::factory()->create(['user_id' => $this->user->id]);
        $others = Expense::factory()->create(['user_id' => $otherUser->id]);

        $deleted = $this->service->batchDelete(
            new ExpenseBatchDeleteQueryDto(
                scope: AccessScope::forUser((int) $this->user->id),
                payload: new BatchDeleteExpenseDto([$mineA->id, $mineB->id, $others->id])
            )
        );

        $this->assertSame(2, $deleted);
        $this->assertDatabaseMissing('expenses', ['id' => $mineA->id]);
        $this->assertDatabaseMissing('expenses', ['id' => $mineB->id]);
        $this->assertDatabaseHas('expenses', ['id' => $others->id]);
    }
}
