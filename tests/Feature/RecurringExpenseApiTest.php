<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Category;
use App\Enums\Intent;
use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class RecurringExpenseApiTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
    }

    #[Test]
    public function can_create_recurring_expense(): void
    {
        $response = $this->postJson('/api/recurring-expenses', [
            'name' => '車貸',
            'amount_min' => 15000,
            'category' => 'living',
            'frequency_type' => 'monthly',
            'frequency_interval' => 1,
            'day_of_month' => 15,
            'start_date' => '2026-02-01',
            'default_intent' => 'necessity',
            'note' => '中古車分期',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '車貸',
                    'amount_min' => '15000.00',
                    'category' => 'living',
                    'category_label' => '生活',
                    'frequency_type' => 'monthly',
                    'frequency_type_label' => '每月',
                    'frequency_interval' => 1,
                    'day_of_month' => 15,
                    'default_intent' => 'necessity',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('recurring_expenses', [
            'name' => '車貸',
            'amount_min' => 15000,
            'category' => 'living',
        ]);
    }

    #[Test]
    public function can_create_recurring_expense_with_amount_range(): void
    {
        $response = $this->postJson('/api/recurring-expenses', [
            'name' => '電費',
            'amount_min' => 800,
            'amount_max' => 2000,
            'category' => 'living',
            'frequency_type' => 'monthly',
            'day_of_month' => 20,
            'start_date' => '2026-02-01',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '電費',
                    'amount_min' => '800.00',
                    'amount_max' => '2000.00',
                    'has_amount_range' => true,
                ],
            ]);
    }

    #[Test]
    public function can_list_recurring_expenses(): void
    {
        RecurringExpense::factory()->count(3)->create();

        $response = $this->getJson('/api/recurring-expenses');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'amount_min', 'amount_display', 'category', 'category_label',
                        'frequency_type', 'frequency_type_label', 'frequency_display',
                        'next_occurrence', 'is_active',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);
    }

    #[Test]
    public function can_filter_recurring_expenses_by_active_status(): void
    {
        RecurringExpense::factory()->count(2)->create(['is_active' => true]);
        RecurringExpense::factory()->count(1)->create(['is_active' => false]);

        $response = $this->getJson('/api/recurring-expenses?is_active=true');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $response = $this->getJson('/api/recurring-expenses?is_active=false');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function can_show_recurring_expense(): void
    {
        $recurringExpense = RecurringExpense::factory()->create();

        $response = $this->getJson("/api/recurring-expenses/{$recurringExpense->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $recurringExpense->id,
                    'name' => $recurringExpense->name,
                ],
            ]);
    }

    #[Test]
    public function can_update_recurring_expense(): void
    {
        $recurringExpense = RecurringExpense::factory()->create(['name' => '舊名稱']);

        $response = $this->putJson("/api/recurring-expenses/{$recurringExpense->id}", [
            'name' => '新名稱',
            'amount_min' => 20000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '新名稱',
                    'amount_min' => '20000.00',
                ],
            ]);

        $this->assertDatabaseHas('recurring_expenses', [
            'id' => $recurringExpense->id,
            'name' => '新名稱',
            'amount_min' => 20000,
        ]);
    }

    #[Test]
    public function can_delete_recurring_expense(): void
    {
        $recurringExpense = RecurringExpense::factory()->create();

        $response = $this->deleteJson("/api/recurring-expenses/{$recurringExpense->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '固定支出已刪除',
            ]);

        $this->assertDatabaseMissing('recurring_expenses', ['id' => $recurringExpense->id]);
    }

    #[Test]
    public function can_get_upcoming_recurring_expenses(): void
    {
        // Create recurring expense due in 3 days
        RecurringExpense::factory()->create([
            'next_occurrence' => Carbon::today()->addDays(3),
            'is_active' => true,
        ]);

        // Create recurring expense due in 10 days (outside default 7 day window)
        RecurringExpense::factory()->create([
            'next_occurrence' => Carbon::today()->addDays(10),
            'is_active' => true,
        ]);

        // Create inactive recurring expense
        RecurringExpense::factory()->create([
            'next_occurrence' => Carbon::today()->addDays(2),
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/recurring-expenses/upcoming');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Test with custom days parameter
        $response = $this->getJson('/api/recurring-expenses/upcoming?days=14');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function can_manually_generate_expense_from_recurring(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'name' => '車貸',
            'amount_min' => 15000,
            'category' => Category::Living,
            'default_intent' => Intent::Necessity,
        ]);

        $response = $this->postJson("/api/recurring-expenses/{$recurringExpense->id}/generate", [
            'date' => now()->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => '已手動生成消費記錄',
            ])
            ->assertJsonPath('data.amount', '15000.00')
            ->assertJsonPath('data.category', 'living')
            ->assertJsonPath('data.recurring_expense_id', $recurringExpense->id)
            ->assertJsonPath('data.is_from_recurring', true);

        $this->assertDatabaseHas('expenses', [
            'recurring_expense_id' => $recurringExpense->id,
            'amount' => 15000,
        ]);

        // Verify decision was created
        $this->assertDatabaseHas('decisions', [
            'intent' => 'necessity',
        ]);
    }

    #[Test]
    public function can_manually_generate_expense_with_custom_amount(): void
    {
        $recurringExpense = RecurringExpense::factory()->create([
            'amount_min' => 800,
            'amount_max' => 2000,
        ]);

        $response = $this->postJson("/api/recurring-expenses/{$recurringExpense->id}/generate", [
            'amount' => 1500,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.amount', '1500.00');
    }

    #[Test]
    public function can_get_recurring_expense_history(): void
    {
        $recurringExpense = RecurringExpense::factory()->create();

        // Create some expenses from this recurring expense
        Expense::factory()->count(3)->create([
            'recurring_expense_id' => $recurringExpense->id,
        ]);

        $response = $this->getJson("/api/recurring-expenses/{$recurringExpense->id}/history");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function validates_required_fields(): void
    {
        $response = $this->postJson('/api/recurring-expenses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'amount_min', 'category', 'frequency_type', 'start_date']);
    }

    #[Test]
    public function validates_amount_max_must_be_greater_than_or_equal_to_amount_min(): void
    {
        $response = $this->postJson('/api/recurring-expenses', [
            'name' => '電費',
            'amount_min' => 2000,
            'amount_max' => 800,
            'category' => 'living',
            'frequency_type' => 'monthly',
            'start_date' => '2026-02-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount_max']);
    }

    #[Test]
    public function validates_end_date_must_be_after_start_date(): void
    {
        $response = $this->postJson('/api/recurring-expenses', [
            'name' => '車貸',
            'amount_min' => 15000,
            'category' => 'living',
            'frequency_type' => 'monthly',
            'start_date' => '2026-02-01',
            'end_date' => '2025-12-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    #[Test]
    public function validates_frequency_type_enum(): void
    {
        $response = $this->postJson('/api/recurring-expenses', [
            'name' => '車貸',
            'amount_min' => 15000,
            'category' => 'living',
            'frequency_type' => 'invalid_type',
            'start_date' => '2026-02-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frequency_type']);
    }

    #[Test]
    public function can_deactivate_and_reactivate_recurring_expense(): void
    {
        $recurringExpense = RecurringExpense::factory()->create(['is_active' => true]);

        // Deactivate
        $response = $this->putJson("/api/recurring-expenses/{$recurringExpense->id}", [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // Reactivate
        $response = $this->putJson("/api/recurring-expenses/{$recurringExpense->id}", [
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', true);
    }

    #[Test]
    public function recurring_expense_endpoints_require_authentication(): void
    {
        $recurringExpense = RecurringExpense::factory()->create();

        auth()->logout();

        $this->getJson('/api/recurring-expenses')->assertStatus(401);
        $this->postJson('/api/recurring-expenses', [])->assertStatus(401);
        $this->getJson("/api/recurring-expenses/{$recurringExpense->id}")->assertStatus(401);
        $this->putJson("/api/recurring-expenses/{$recurringExpense->id}", [])->assertStatus(401);
        $this->deleteJson("/api/recurring-expenses/{$recurringExpense->id}")->assertStatus(401);
        $this->getJson('/api/recurring-expenses/upcoming')->assertStatus(401);
        $this->postJson("/api/recurring-expenses/{$recurringExpense->id}/generate", [])->assertStatus(401);
        $this->getJson("/api/recurring-expenses/{$recurringExpense->id}/history")->assertStatus(401);
    }

    #[Test]
    public function cannot_access_another_users_recurring_expense_resources(): void
    {
        $owner = User::factory()->create();
        $otherUserRecurringExpense = RecurringExpense::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this->getJson("/api/recurring-expenses/{$otherUserRecurringExpense->id}")
            ->assertStatus(404);
        $this->putJson("/api/recurring-expenses/{$otherUserRecurringExpense->id}", [
            'name' => 'not allowed',
        ])->assertStatus(404);
        $this->deleteJson("/api/recurring-expenses/{$otherUserRecurringExpense->id}")
            ->assertStatus(404);
        $this->postJson("/api/recurring-expenses/{$otherUserRecurringExpense->id}/generate", [
            'amount' => 1000,
        ])->assertStatus(404);
        $this->getJson("/api/recurring-expenses/{$otherUserRecurringExpense->id}/history")
            ->assertStatus(404);
    }
}
