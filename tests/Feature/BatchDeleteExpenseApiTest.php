<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Decision;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class BatchDeleteExpenseApiTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
    }

    public function testCanBatchDeleteExpenses(): void
    {
        $expenses = Expense::factory()->count(3)->create();
        $ids = $expenses->pluck('id')->toArray();

        $response = $this->deleteJson('/api/expenses/batch', [
            'ids' => $ids,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '成功刪除 3 筆消費記錄',
                'data' => [
                    'deleted_count' => 3,
                ],
            ]);

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('expenses', ['id' => $id]);
        }
    }

    public function testBatchDeleteWithAssociatedDecisions(): void
    {
        $expenses = Expense::factory()->count(2)->create();

        foreach ($expenses as $expense) {
            Decision::factory()->create(['expense_id' => $expense->id]);
        }

        $ids = $expenses->pluck('id')->toArray();

        $response = $this->deleteJson('/api/expenses/batch', [
            'ids' => $ids,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deleted_count' => 2,
                ],
            ]);

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('expenses', ['id' => $id]);
            $this->assertDatabaseMissing('decisions', ['expense_id' => $id]);
        }
    }

    public function testBatchDeleteWithSomeNonExistentIds(): void
    {
        $expenses = Expense::factory()->count(2)->create();
        $existingIds = $expenses->pluck('id')->toArray();
        $nonExistentIds = [99998, 99999];
        $allIds = array_merge($existingIds, $nonExistentIds);

        $response = $this->deleteJson('/api/expenses/batch', [
            'ids' => $allIds,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deleted_count' => 2,
                ],
            ]);

        foreach ($existingIds as $id) {
            $this->assertDatabaseMissing('expenses', ['id' => $id]);
        }
    }

    public function testBatchDeleteWithAllNonExistentIds(): void
    {
        $response = $this->deleteJson('/api/expenses/batch', [
            'ids' => [99998, 99999],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deleted_count' => 0,
                ],
            ]);
    }

    public function testBatchDeleteValidatesIdsRequired(): void
    {
        $response = $this->deleteJson('/api/expenses/batch', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    public function testBatchDeleteValidatesIdsMustBeArray(): void
    {
        $response = $this->deleteJson('/api/expenses/batch', [
            'ids' => 'not-an-array',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    public function testBatchDeleteValidatesEmptyArray(): void
    {
        $response = $this->deleteJson('/api/expenses/batch', [
            'ids' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    public function testBatchDeleteValidatesMax100Ids(): void
    {
        $ids = range(1, 101);

        $response = $this->deleteJson('/api/expenses/batch', [
            'ids' => $ids,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    public function testBatchDeleteValidatesIdsMustBeIntegers(): void
    {
        $response = $this->deleteJson('/api/expenses/batch', [
            'ids' => ['a', 'b', 'c'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids.0', 'ids.1', 'ids.2']);
    }

    public function testBatchDeleteValidatesIdsMustBePositive(): void
    {
        $response = $this->deleteJson('/api/expenses/batch', [
            'ids' => [0, -1, -2],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids.0', 'ids.1', 'ids.2']);
    }

    public function testBatchDeleteWithSingleId(): void
    {
        $expense = Expense::factory()->create();

        $response = $this->deleteJson('/api/expenses/batch', [
            'ids' => [$expense->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deleted_count' => 1,
                ],
            ]);

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function testBatchDeleteWithDuplicateIds(): void
    {
        $expense = Expense::factory()->create();

        $response = $this->deleteJson('/api/expenses/batch', [
            'ids' => [$expense->id, $expense->id, $expense->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deleted_count' => 1,
                ],
            ]);

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }
}
