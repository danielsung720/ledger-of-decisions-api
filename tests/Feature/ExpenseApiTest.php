<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Category;
use App\Enums\ConfidenceLevel;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class ExpenseApiTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
    }

    #[Test]
    public function CanCreateExpense(): void
    {
        $response = $this->postJson('/api/expenses', [
            'amount' => 100.50,
            'category' => Category::Food->value,
            'occurred_at' => '2026-02-06 12:00:00',
            'note' => '午餐',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'amount' => '100.50',
                    'currency' => 'TWD',
                    'category' => Category::Food->value,
                    'category_label' => '飲食',
                    'note' => '午餐',
                ],
            ]);

        $this->assertDatabaseHas('expenses', [
            'user_id' => $this->user->id,
            'amount' => 100.50,
            'category' => Category::Food->value,
        ]);
    }

    #[Test]
    public function CanListExpenses(): void
    {
        Expense::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/expenses');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'amount', 'currency', 'category', 'category_label', 'occurred_at', 'note'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);
    }

    #[Test]
    public function CanShowExpense(): void
    {
        $expense = Expense::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/expenses/{$expense->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $expense->id,
                ],
            ]);
    }

    #[Test]
    public function CanUpdateExpense(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100,
            'note' => 'old',
        ]);

        $response = $this->putJson("/api/expenses/{$expense->id}", [
            'amount' => 200,
            'note' => '更新後的備註',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'amount' => '200.00',
                    'note' => '更新後的備註',
                ],
            ]);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'amount' => 200,
            'note' => '更新後的備註',
        ]);
    }

    #[Test]
    public function CanDeleteExpense(): void
    {
        $expense = Expense::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/expenses/{$expense->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '消費記錄已刪除',
            ]);

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    #[Test]
    public function CanFilterExpensesByCategory(): void
    {
        Expense::factory()->create(['user_id' => $this->user->id, 'category' => Category::Food]);
        Expense::factory()->create(['user_id' => $this->user->id, 'category' => Category::Transport]);
        Expense::factory()->create(['user_id' => $this->user->id, 'category' => Category::Food]);

        $response = $this->getJson('/api/expenses?category=food');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function CanFilterExpensesByDateRange(): void
    {
        Expense::factory()->create(['user_id' => $this->user->id, 'occurred_at' => '2026-02-01 10:00:00']);
        Expense::factory()->create(['user_id' => $this->user->id, 'occurred_at' => '2026-02-05 10:00:00']);
        Expense::factory()->create(['user_id' => $this->user->id, 'occurred_at' => '2026-02-10 10:00:00']);

        $response = $this->getJson('/api/expenses?start_date=2026-02-04&end_date=2026-02-06');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function CanFilterExpensesByIntentAndConfidenceLevel(): void
    {
        $expenseA = Expense::factory()->create(['user_id' => $this->user->id]);
        Decision::factory()->create([
            'expense_id' => $expenseA->id,
            'intent' => Intent::Necessity,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        $expenseB = Expense::factory()->create(['user_id' => $this->user->id]);
        Decision::factory()->create([
            'expense_id' => $expenseB->id,
            'intent' => Intent::Impulse,
            'confidence_level' => ConfidenceLevel::Low,
        ]);

        $response = $this->getJson('/api/expenses?intent=necessity&confidence_level=high');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $expenseA->id);
    }

    #[Test]
    public function PerPageIsLimitedTo100(): void
    {
        Expense::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/expenses?per_page=1000');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, $response->json('meta.per_page'));
    }

    #[Test]
    public function ValidatesRequiredFields(): void
    {
        $response = $this->postJson('/api/expenses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'category', 'occurred_at']);
    }

    #[Test]
    public function ValidatesCategoryEnum(): void
    {
        $response = $this->postJson('/api/expenses', [
            'amount' => 100,
            'category' => 'invalid_category',
            'occurred_at' => '2026-02-06 12:00:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    #[Test]
    public function ValidatesIntentEnumInQueryFilters(): void
    {
        $response = $this->getJson('/api/expenses?intent=invalid_intent');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['intent']);
    }

    #[Test]
    public function ValidatesConfidenceLevelEnumInQueryFilters(): void
    {
        $response = $this->getJson('/api/expenses?confidence_level=invalid_level');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['confidence_level']);
    }

    #[Test]
    public function ExpenseEndpointsRequireAuthentication(): void
    {
        $expense = Expense::factory()->create(['user_id' => $this->user->id]);

        auth()->logout();

        $this->getJson('/api/expenses')->assertStatus(401);
        $this->postJson('/api/expenses', [])->assertStatus(401);
        $this->getJson("/api/expenses/{$expense->id}")->assertStatus(401);
        $this->putJson("/api/expenses/{$expense->id}", [])->assertStatus(401);
        $this->deleteJson("/api/expenses/{$expense->id}")->assertStatus(401);
        $this->deleteJson('/api/expenses/batch', ['ids' => [$expense->id]])->assertStatus(401);
    }

    #[Test]
    public function CannotAccessOtherUsersExpenseById(): void
    {
        $otherUser = User::factory()->create();
        $otherExpense = Expense::factory()->create(['user_id' => $otherUser->id]);

        $this->getJson("/api/expenses/{$otherExpense->id}")->assertStatus(404);
        $this->putJson("/api/expenses/{$otherExpense->id}", ['note' => 'Nope'])->assertStatus(404);
        $this->deleteJson("/api/expenses/{$otherExpense->id}")->assertStatus(404);
    }

    #[Test]
    public function ListExpensesExcludesOtherUsersData(): void
    {
        $otherUser = User::factory()->create();

        Expense::factory()->count(2)->create(['user_id' => $this->user->id]);
        Expense::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/expenses');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }
}
