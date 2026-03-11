<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ConfidenceLevel;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class DecisionApiTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
    }

    #[Test]
    public function can_create_decision_for_expense(): void
    {
        $expense = Expense::factory()->create();

        $response = $this->postJson("/api/expenses/{$expense->id}/decision", [
            'intent' => 'necessity',
            'confidence_level' => 'high',
            'decision_note' => '這是必要的開支',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'expense_id' => $expense->id,
                    'intent' => 'necessity',
                    'intent_label' => '必要性',
                    'confidence_level' => 'high',
                    'confidence_level_label' => '高',
                    'decision_note' => '這是必要的開支',
                ],
            ]);

        $this->assertDatabaseHas('decisions', [
            'expense_id' => $expense->id,
            'intent' => 'necessity',
        ]);
    }

    #[Test]
    public function cannot_create_duplicate_decision(): void
    {
        $expense = Expense::factory()->create();
        Decision::factory()->create(['expense_id' => $expense->id]);

        $response = $this->postJson("/api/expenses/{$expense->id}/decision", [
            'intent' => 'efficiency',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => '此消費記錄已有決策標註，請使用更新 API',
            ]);
    }

    #[Test]
    public function can_show_decision(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create(['expense_id' => $expense->id]);

        $response = $this->getJson("/api/expenses/{$expense->id}/decision");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $decision->id,
                    'expense_id' => $expense->id,
                ],
            ]);
    }

    #[Test]
    public function returns404_when_no_decision(): void
    {
        $expense = Expense::factory()->create();

        $response = $this->getJson("/api/expenses/{$expense->id}/decision");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '此消費記錄尚無決策標註',
            ]);
    }

    #[Test]
    public function can_update_decision(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create([
            'expense_id' => $expense->id,
            'intent' => Intent::Necessity,
        ]);

        $response = $this->putJson("/api/expenses/{$expense->id}/decision", [
            'intent' => 'impulse',
            'confidence_level' => 'low',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'intent' => 'impulse',
                    'intent_label' => '衝動',
                    'confidence_level' => 'low',
                    'confidence_level_label' => '低',
                ],
            ]);
    }

    #[Test]
    public function can_update_decision_and_clear_confidence_level_with_null(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create([
            'expense_id' => $expense->id,
            'intent' => Intent::Necessity,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        $response = $this->putJson("/api/expenses/{$expense->id}/decision", [
            'intent' => 'necessity',
            'confidence_level' => null,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.intent', 'necessity')
            ->assertJsonPath('data.confidence_level', null)
            ->assertJsonPath('data.confidence_level_label', null);

        $this->assertDatabaseHas('decisions', [
            'id' => $decision->id,
            'intent' => 'necessity',
            'confidence_level' => null,
        ]);
    }

    #[Test]
    public function update_decision_rejects_invalid_confidence_level(): void
    {
        $expense = Expense::factory()->create();
        Decision::factory()->create([
            'expense_id' => $expense->id,
            'intent' => Intent::Necessity,
        ]);

        $response = $this->putJson("/api/expenses/{$expense->id}/decision", [
            'intent' => 'necessity',
            'confidence_level' => 'invalid-level',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['confidence_level']);
    }

    #[Test]
    public function update_returns404_when_no_decision(): void
    {
        $expense = Expense::factory()->create();

        $response = $this->putJson("/api/expenses/{$expense->id}/decision", [
            'intent' => 'impulse',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '此消費記錄尚無決策標註，請使用新增 API',
            ]);
    }

    #[Test]
    public function can_delete_decision(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create(['expense_id' => $expense->id]);

        $response = $this->deleteJson("/api/expenses/{$expense->id}/decision");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '決策標註已刪除',
            ]);

        $this->assertDatabaseMissing('decisions', ['id' => $decision->id]);
    }

    #[Test]
    public function delete_returns404_when_no_decision(): void
    {
        $expense = Expense::factory()->create();

        $response = $this->deleteJson("/api/expenses/{$expense->id}/decision");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '此消費記錄尚無決策標註',
            ]);
    }

    #[Test]
    public function validates_intent_enum(): void
    {
        $expense = Expense::factory()->create();

        $response = $this->postJson("/api/expenses/{$expense->id}/decision", [
            'intent' => 'invalid_intent',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['intent']);
    }

    #[Test]
    public function decision_endpoints_require_authentication(): void
    {
        $expense = Expense::factory()->create();

        auth()->logout();

        $this->postJson("/api/expenses/{$expense->id}/decision", [])->assertStatus(401);
        $this->getJson("/api/expenses/{$expense->id}/decision")->assertStatus(401);
        $this->putJson("/api/expenses/{$expense->id}/decision", [])->assertStatus(401);
        $this->deleteJson("/api/expenses/{$expense->id}/decision")->assertStatus(401);
    }

    #[Test]
    public function cannot_access_another_users_expense_decision_resources(): void
    {
        $owner = User::factory()->create();
        $otherUserExpense = Expense::factory()->create([
            'user_id' => $owner->id,
        ]);

        $this->postJson("/api/expenses/{$otherUserExpense->id}/decision", [
            'intent' => 'necessity',
        ])->assertStatus(404);

        $this->getJson("/api/expenses/{$otherUserExpense->id}/decision")->assertStatus(404);

        $this->putJson("/api/expenses/{$otherUserExpense->id}/decision", [
            'intent' => 'impulse',
        ])->assertStatus(404);

        $this->deleteJson("/api/expenses/{$otherUserExpense->id}/decision")->assertStatus(404);
    }
}
