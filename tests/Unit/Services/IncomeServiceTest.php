<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Income\CreateIncomeDto;
use App\DTO\Income\IncomeFiltersDto;
use App\DTO\Income\IncomePaginateQueryDto;
use App\DTO\Income\UpdateIncomeDto;
use App\Enums\CashFlowFrequencyType;
use App\Events\IncomeCreated;
use App\Events\IncomeDeleted;
use App\Events\IncomeUpdated;
use App\Models\Income;
use App\Models\User;
use App\Repositories\IncomeRepository;
use App\Services\IncomeService;
use App\Support\AccessScope;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class IncomeServiceTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    private IncomeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        $this->service = new IncomeService(new IncomeRepository());
    }

    #[Test]
    public function PaginateShouldFilterByAccessScopeAndConditions(): void
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

        $result = $this->service->paginate(
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
    public function CreateShouldPersistIncomeForAuthenticatedUser(): void
    {
        Event::fake([IncomeCreated::class]);

        $income = $this->service->create(
            new CreateIncomeDto(
                name: 'Service Create',
                amount: 12345,
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
            'name' => 'Service Create',
            'amount' => 12345,
        ]);
        Event::assertDispatched(IncomeCreated::class, function (IncomeCreated $event) use ($income): bool {
            return $event->income->id === $income->id;
        });
    }

    #[Test]
    public function UpdateShouldModifyIncomeFields(): void
    {
        Event::fake([IncomeUpdated::class]);

        $income = Income::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Name',
            'amount' => 50000,
        ]);

        $updated = $this->service->update(
            $income,
            UpdateIncomeDto::fromArray([
                'name' => 'New Name',
                'amount' => 55000,
            ])
        );

        $this->assertSame('New Name', $updated->name);
        $this->assertSame('55000.00', $updated->amount);
        $this->assertDatabaseHas('incomes', [
            'id' => $income->id,
            'name' => 'New Name',
            'amount' => 55000,
        ]);
        Event::assertDispatched(IncomeUpdated::class, function (IncomeUpdated $event) use ($income): bool {
            return $event->income->id === $income->id;
        });
    }

    #[Test]
    public function DeleteShouldRemoveIncome(): void
    {
        Event::fake([IncomeDeleted::class]);

        $income = Income::factory()->create(['user_id' => $this->user->id]);

        $this->service->delete($income);

        $this->assertDatabaseMissing('incomes', ['id' => $income->id]);
        Event::assertDispatched(IncomeDeleted::class, function (IncomeDeleted $event) use ($income): bool {
            return $event->income->id === $income->id;
        });
    }
}
