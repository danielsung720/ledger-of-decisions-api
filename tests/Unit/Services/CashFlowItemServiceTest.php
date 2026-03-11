<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\CashFlowItem\CashFlowItemFiltersDto;
use App\DTO\CashFlowItem\CashFlowItemPaginateQueryDto;
use App\DTO\CashFlowItem\CreateCashFlowItemDto;
use App\DTO\CashFlowItem\UpdateCashFlowItemDto;
use App\Enums\CashFlowFrequencyType;
use App\Enums\Category;
use App\Events\CashFlowItemCreated;
use App\Events\CashFlowItemDeleted;
use App\Events\CashFlowItemUpdated;
use App\Models\CashFlowItem;
use App\Models\User;
use App\Repositories\CashFlowItemRepository;
use App\Services\CashFlowItemService;
use App\Support\AccessScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class CashFlowItemServiceTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    private CashFlowItemService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        $this->service = new CashFlowItemService(new CashFlowItemRepository());
    }

    #[Test]
    public function PaginateShouldFilterByAccessScopeAndConditions(): void
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

        $result = $this->service->paginate(
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
    public function CreateShouldPersistCashFlowItemForAuthenticatedUser(): void
    {
        Event::fake([CashFlowItemCreated::class]);

        $item = $this->service->create(
            new CreateCashFlowItemDto(
                name: 'Service Create Item',
                amount: 12345,
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
            'name' => 'Service Create Item',
            'amount' => 12345,
        ]);
        Event::assertDispatched(CashFlowItemCreated::class, function (CashFlowItemCreated $event) use ($item): bool {
            return $event->cashFlowItem->id === $item->id;
        });
    }

    #[Test]
    public function ShowShouldReturnCashFlowItemInstance(): void
    {
        $item = CashFlowItem::factory()->create(['user_id' => $this->user->id]);

        $result = $this->service->show($item);

        $this->assertSame($item->id, $result->id);
    }

    #[Test]
    public function UpdateShouldModifyCashFlowItemFields(): void
    {
        Event::fake([CashFlowItemUpdated::class]);

        $item = CashFlowItem::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Item Name',
            'amount' => 50000,
        ]);

        $updated = $this->service->update(
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
        Event::assertDispatched(CashFlowItemUpdated::class, function (CashFlowItemUpdated $event) use ($item): bool {
            return $event->cashFlowItem->id === $item->id;
        });
    }

    #[Test]
    public function DeleteShouldRemoveCashFlowItem(): void
    {
        Event::fake([CashFlowItemDeleted::class]);

        $item = CashFlowItem::factory()->create(['user_id' => $this->user->id]);

        $this->service->delete($item);

        $this->assertDatabaseMissing('cash_flow_items', ['id' => $item->id]);
        Event::assertDispatched(CashFlowItemDeleted::class, function (CashFlowItemDeleted $event) use ($item): bool {
            return $event->cashFlowItem->id === $item->id;
        });
    }
}
