<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\DTO\Income\CreateIncomeDto;
use App\DTO\Income\IncomeFiltersDto;
use App\DTO\Income\IncomePaginateQueryDto;
use App\DTO\Income\UpdateIncomeDto;
use App\Enums\CashFlowFrequencyType;
use App\Models\Income;
use App\Models\User;
use App\Repositories\IncomeRepository;
use App\Support\AccessScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class IncomeRepositoryTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    private IncomeRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        $this->repository = new IncomeRepository();
    }

    #[Test]
    public function PaginateShouldApplyScopeAndFilters(): void
    {
        $otherUser = User::factory()->create();

        Income::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My Monthly Active',
            'frequency_type' => CashFlowFrequencyType::Monthly,
            'is_active' => true,
        ]);

        Income::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My Yearly Active',
            'frequency_type' => CashFlowFrequencyType::Yearly,
            'is_active' => true,
        ]);

        Income::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My Monthly Inactive',
            'frequency_type' => CashFlowFrequencyType::Monthly,
            'is_active' => false,
        ]);

        Income::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other Monthly Active',
            'frequency_type' => CashFlowFrequencyType::Monthly,
            'is_active' => true,
        ]);

        $result = $this->repository->paginate(
            new IncomePaginateQueryDto(
                scope: AccessScope::forUser((int) $this->user->id),
                filters: new IncomeFiltersDto(
                    isActive: true,
                    frequencyTypes: [CashFlowFrequencyType::Monthly->value],
                    perPage: 15
                )
            )
        );

        $this->assertSame(1, $result->total());
        $this->assertSame('My Monthly Active', $result->items()[0]->name);
    }

    #[Test]
    public function CreateShouldPersistIncome(): void
    {
        $income = $this->repository->create(
            new CreateIncomeDto(
                name: 'Repo Create',
                amount: 32100,
                currency: null,
                frequencyType: CashFlowFrequencyType::Monthly->value,
                frequencyInterval: null,
                startDate: '2026-02-01',
                endDate: null,
                note: null
            )
        );

        $this->assertDatabaseHas('incomes', [
            'id' => $income->id,
            'user_id' => $this->user->id,
            'name' => 'Repo Create',
            'amount' => 32100,
        ]);
    }

    #[Test]
    public function UpdateShouldPersistChangedFields(): void
    {
        $income = Income::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Name',
            'amount' => 10000,
        ]);

        $updated = $this->repository->update(
            $income,
            UpdateIncomeDto::fromArray([
                'name' => 'New Name',
                'amount' => 12000,
            ])
        );

        $this->assertSame('New Name', $updated->name);
        $this->assertSame('12000.00', $updated->amount);
        $this->assertDatabaseHas('incomes', [
            'id' => $income->id,
            'name' => 'New Name',
            'amount' => 12000,
        ]);
    }

    #[Test]
    public function DeleteShouldRemoveIncome(): void
    {
        $income = Income::factory()->create(['user_id' => $this->user->id]);

        $this->repository->delete($income);

        $this->assertDatabaseMissing('incomes', ['id' => $income->id]);
    }
}
