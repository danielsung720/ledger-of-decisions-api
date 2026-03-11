<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Decision\CreateDecisionDto;
use App\DTO\Decision\UpdateDecisionDto;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use App\Repositories\DecisionRepository;
use App\Services\DecisionService;
use App\Support\AccessScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DecisionServiceTest extends TestCase
{
    use RefreshDatabase;

    private DecisionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DecisionService(new DecisionRepository);
    }

    #[Test]
    public function create_for_expense_should_return_null_when_decision_already_exists(): void
    {
        $expense = Expense::factory()->create();
        Decision::factory()->create(['expense_id' => $expense->id]);
        $scope = AccessScope::forUser((int) $expense->user_id);

        $decision = $this->service->createForExpense($scope, $expense, new CreateDecisionDto('necessity', null, null));

        $this->assertNull($decision);
    }

    #[Test]
    public function show_for_expense_should_return_decision_when_exists(): void
    {
        $expense = Expense::factory()->create();
        $expected = Decision::factory()->create(['expense_id' => $expense->id]);
        $scope = AccessScope::forUser((int) $expense->user_id);

        $decision = $this->service->showForExpense($scope, $expense);

        $this->assertNotNull($decision);
        $this->assertSame($expected->id, $decision->id);
    }

    #[Test]
    public function update_for_expense_should_return_null_when_decision_missing(): void
    {
        $expense = Expense::factory()->create();
        $scope = AccessScope::forUser((int) $expense->user_id);

        $decision = $this->service->updateForExpense($scope, $expense, new UpdateDecisionDto(['intent' => 'impulse']));

        $this->assertNull($decision);
    }

    #[Test]
    public function delete_for_expense_should_delete_when_decision_exists(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create(['expense_id' => $expense->id]);
        $scope = AccessScope::forUser((int) $expense->user_id);

        $deleted = $this->service->deleteForExpense($scope, $expense);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('decisions', ['id' => $decision->id]);
    }

    #[Test]
    public function update_for_expense_should_persist_payload(): void
    {
        $expense = Expense::factory()->create();
        Decision::factory()->create(['expense_id' => $expense->id, 'intent' => Intent::Necessity]);
        $scope = AccessScope::forUser((int) $expense->user_id);

        $updated = $this->service->updateForExpense(
            $scope,
            $expense,
            new UpdateDecisionDto(['intent' => Intent::Impulse->value])
        );

        $this->assertNotNull($updated);
        $this->assertSame(Intent::Impulse, $updated->intent);
    }
}
