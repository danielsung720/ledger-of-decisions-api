<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\DTO\Decision\CreateDecisionDto;
use App\DTO\Decision\UpdateDecisionDto;
use App\Enums\ConfidenceLevel;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use App\Repositories\DecisionRepository;
use App\Support\AccessScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DecisionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DecisionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DecisionRepository;
    }

    #[Test]
    public function create_should_persist_decision_for_expense(): void
    {
        $expense = Expense::factory()->create();
        $scope = AccessScope::forUser((int) $expense->user_id);

        $decision = $this->repository->create(
            $scope,
            $expense,
            new CreateDecisionDto('necessity', 'high', 'note')
        );

        $this->assertDatabaseHas('decisions', [
            'id' => $decision->id,
            'expense_id' => $expense->id,
            'intent' => Intent::Necessity->value,
            'confidence_level' => ConfidenceLevel::High->value,
        ]);
    }

    #[Test]
    public function show_should_return_null_when_decision_does_not_exist(): void
    {
        $expense = Expense::factory()->create();
        $scope = AccessScope::forUser((int) $expense->user_id);

        $this->assertNull($this->repository->show($scope, $expense));
    }

    #[Test]
    public function update_should_persist_changed_values(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create([
            'expense_id' => $expense->id,
            'intent' => Intent::Necessity,
            'confidence_level' => ConfidenceLevel::Medium,
        ]);
        $scope = AccessScope::forUser((int) $expense->user_id);

        $updated = $this->repository->update(
            $scope,
            $expense,
            new UpdateDecisionDto([
                'intent' => Intent::Impulse->value,
                'confidence_level' => ConfidenceLevel::Low->value,
            ])
        );

        $this->assertSame(Intent::Impulse, $updated->intent);
        $this->assertSame(ConfidenceLevel::Low, $updated->confidence_level);
    }

    #[Test]
    public function update_should_persist_null_confidence_level(): void
    {
        $expense = Expense::factory()->create();
        Decision::factory()->create([
            'expense_id' => $expense->id,
            'intent' => Intent::Necessity,
            'confidence_level' => ConfidenceLevel::High,
        ]);
        $scope = AccessScope::forUser((int) $expense->user_id);

        $updated = $this->repository->update(
            $scope,
            $expense,
            new UpdateDecisionDto([
                'intent' => Intent::Necessity->value,
                'confidence_level' => null,
            ])
        );

        $this->assertNotNull($updated);
        $this->assertNull($updated->confidence_level);
    }

    #[Test]
    public function delete_should_remove_decision(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create(['expense_id' => $expense->id]);
        $scope = AccessScope::forUser((int) $expense->user_id);

        $deleted = $this->repository->delete($scope, $expense);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('decisions', ['id' => $decision->id]);
    }
}
