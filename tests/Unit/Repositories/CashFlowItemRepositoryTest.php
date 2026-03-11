<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\DTO\CashFlowItem\CashFlowItemFiltersDto;
use App\DTO\CashFlowItem\CashFlowItemPaginateQueryDto;
use App\DTO\CashFlowItem\CreateCashFlowItemDto;
use App\DTO\CashFlowItem\UpdateCashFlowItemDto;
use App\Enums\CashFlowFrequencyType;
use App\Enums\Category;
use App\Models\CashFlowItem;
use App\Models\User;
use App\Repositories\CashFlowItemRepository;
use App\Support\AccessScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class CashFlowItemRepositoryTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    private CashFlowItemRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        $this->repository = new CashFlowItemRepository();
    }

    #[Test]
    public function PaginateShouldApplyScopeAndFilters(): void
    {
        $otherUser = User::factory()->create();

        CashFlowItem::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My Living Active',
            'category' => Category::Living,
            'frequency_type' => CashFlowFrequencyType::Monthly,
            'is_active' => true,
        ]);

        CashFlowItem::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My Food Active',
            'category' => Category::Food,
            'frequency_type' => CashFlowFrequencyType::Monthly,
            'is_active' => true,
        ]);

        CashFlowItem::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My Living Inactive',
            'category' => Category::Living,
            'frequency_type' => CashFlowFrequencyType::Monthly,
            'is_active' => false,
        ]);

        CashFlowItem::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other Living Active',
            'category' => Category::Living,
            'frequency_type' => CashFlowFrequencyType::Monthly,
            'is_active' => true,
        ]);

        $result = $this->repository->paginate(
            new CashFlowItemPaginateQueryDto(
                scope: AccessScope::forUser((int) $this->user->id),
                filters: new CashFlowItemFiltersDto(
                    categories: [Category::Living->value],
                    isActive: true,
                    frequencyTypes: [CashFlowFrequencyType::Monthly->value],
                    perPage: 15,
                )
            )
        );

        $this->assertSame(1, $result->total());
        $this->assertSame('My Living Active', $result->items()[0]->name);
    }

    #[Test]
    public function CreateShouldPersistCashFlowItem(): void
    {
        $item = $this->repository->create(
            new CreateCashFlowItemDto(
                name: 'Repo Create Item',
                amount: 32100,
                currency: null,
                category: Category::Living->value,
                frequencyType: CashFlowFrequencyType::Monthly->value,
                frequencyInterval: null,
                startDate: '2026-02-01',
                endDate: null,
                note: null,
            )
        );

        $this->assertDatabaseHas('cash_flow_items', [
            'id' => $item->id,
            'user_id' => $this->user->id,
            'name' => 'Repo Create Item',
            'amount' => 32100,
        ]);
    }

    #[Test]
    public function UpdateShouldPersistChangedFields(): void
    {
        $item = CashFlowItem::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Item Name',
            'amount' => 50000,
        ]);

        $updated = $this->repository->update(
            $item,
            UpdateCashFlowItemDto::fromArray([
                'name' => 'New Item Name',
                'amount' => 55000,
            ])
        );

        $this->assertSame('New Item Name', $updated->name);
        $this->assertSame('55000.00', $updated->amount);
        $this->assertDatabaseHas('cash_flow_items', [
            'id' => $item->id,
            'name' => 'New Item Name',
            'amount' => 55000,
        ]);
    }

    #[Test]
    public function DeleteShouldRemoveCashFlowItem(): void
    {
        $item = CashFlowItem::factory()->create(['user_id' => $this->user->id]);

        $this->repository->delete($item);

        $this->assertDatabaseMissing('cash_flow_items', ['id' => $item->id]);
    }
}
