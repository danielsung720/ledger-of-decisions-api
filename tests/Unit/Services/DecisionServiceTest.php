<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Decision\CreateDecisionDto;
use App\DTO\Decision\UpdateDecisionDto;
use App\Enums\CacheDomainEnum;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use App\Repositories\DecisionRepository;
use App\Services\ApiReadCacheService;
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
    public function CreateForExpenseShouldReturnNullWhenDecisionAlreadyExists(): void
    {
        $expense = Expense::factory()->create();
        Decision::factory()->create(['expense_id' => $expense->id]);
        $scope = AccessScope::forUser((int) $expense->user_id);

        $decision = $this->service->createForExpense($scope, $expense, new CreateDecisionDto('necessity', null, null));

        $this->assertNull($decision);
    }

    #[Test]
    public function ShowForExpenseShouldReturnDecisionWhenExists(): void
    {
        $expense = Expense::factory()->create();
        $expected = Decision::factory()->create(['expense_id' => $expense->id]);
        $scope = AccessScope::forUser((int) $expense->user_id);

        $decision = $this->service->showForExpense($scope, $expense);

        $this->assertNotNull($decision);
        $this->assertSame($expected->id, $decision->id);
    }

    #[Test]
    public function UpdateForExpenseShouldReturnNullWhenDecisionMissing(): void
    {
        $expense = Expense::factory()->create();
        $scope = AccessScope::forUser((int) $expense->user_id);

        $decision = $this->service->updateForExpense($scope, $expense, new UpdateDecisionDto(['intent' => 'impulse']));

        $this->assertNull($decision);
    }

    #[Test]
    public function DeleteForExpenseShouldDeleteWhenDecisionExists(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create(['expense_id' => $expense->id]);
        $scope = AccessScope::forUser((int) $expense->user_id);

        $deleted = $this->service->deleteForExpense($scope, $expense);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('decisions', ['id' => $decision->id]);
    }

    #[Test]
    public function UpdateForExpenseShouldPersistPayload(): void
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

    #[Test]
    public function CreateForExpenseShouldInvalidateStatisticsAndExpensesCacheVersions(): void
    {
        $repository = $this->createMock(DecisionRepository::class);
        $cacheService = $this->createMock(ApiReadCacheService::class);
        $expense = new Expense(['user_id' => 31]);
        $calls = [];

        $repository->expects($this->once())
            ->method('create')
            ->willReturn(new Decision());

        $cacheService->expects($this->exactly(2))
            ->method('invalidateDomainVersion')
            ->willReturnCallback(function (int $userId, CacheDomainEnum $domain) use (&$calls): int {
                $calls[] = [$userId, $domain->value];

                return 2;
            });

        $service = new DecisionService($repository, $cacheService);
        $service->createForExpense(
            AccessScope::forUser(31),
            $expense,
            new CreateDecisionDto('necessity', null, null)
        );

        $this->assertSame([[31, 'Statistics'], [31, 'Expenses']], $calls);
    }

    #[Test]
    public function UpdateForExpenseShouldInvalidateStatisticsAndExpensesCacheVersions(): void
    {
        $repository = $this->createMock(DecisionRepository::class);
        $cacheService = $this->createMock(ApiReadCacheService::class);
        $expense = new Expense(['user_id' => 42]);
        $expense->setRelation('decision', new Decision());
        $calls = [];

        $repository->expects($this->once())
            ->method('update')
            ->willReturn(new Decision());

        $cacheService->expects($this->exactly(2))
            ->method('invalidateDomainVersion')
            ->willReturnCallback(function (int $userId, CacheDomainEnum $domain) use (&$calls): int {
                $calls[] = [$userId, $domain->value];

                return 2;
            });

        $service = new DecisionService($repository, $cacheService);
        $service->updateForExpense(
            AccessScope::forUser(42),
            $expense,
            new UpdateDecisionDto(['intent' => 'impulse'])
        );

        $this->assertSame([[42, 'Statistics'], [42, 'Expenses']], $calls);
    }

    #[Test]
    public function DeleteForExpenseShouldInvalidateStatisticsAndExpensesCacheVersions(): void
    {
        $repository = $this->createMock(DecisionRepository::class);
        $cacheService = $this->createMock(ApiReadCacheService::class);
        $expense = new Expense(['user_id' => 53]);
        $expense->setRelation('decision', new Decision());
        $calls = [];

        $repository->expects($this->once())->method('delete');

        $cacheService->expects($this->exactly(2))
            ->method('invalidateDomainVersion')
            ->willReturnCallback(function (int $userId, CacheDomainEnum $domain) use (&$calls): int {
                $calls[] = [$userId, $domain->value];

                return 2;
            });

        $service = new DecisionService($repository, $cacheService);
        $deleted = $service->deleteForExpense(AccessScope::forUser(53), $expense);

        $this->assertTrue($deleted);
        $this->assertSame([[53, 'Statistics'], [53, 'Expenses']], $calls);
    }
}
