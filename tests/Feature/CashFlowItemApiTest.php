<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Category;
use App\Models\CashFlowItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class CashFlowItemApiTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
    }

    #[Test]
    public function CanCreateCashFlowItem(): void
    {
        $response = $this->postJson('/api/cash-flow-items', [
            'name' => '房租',
            'amount' => 25000,
            'category' => 'living',
            'frequency_type' => 'monthly',
            'frequency_interval' => 1,
            'start_date' => '2026-02-01',
            'note' => '台北租屋',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '房租',
                    'amount' => '25000.00',
                    'category' => 'living',
                    'category_label' => '生活',
                    'frequency_type' => 'monthly',
                    'frequency_type_label' => '每月',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('cash_flow_items', [
            'name' => '房租',
            'amount' => 25000,
            'category' => 'living',
        ]);
    }

    #[Test]
    public function CanCreateYearlyCashFlowItem(): void
    {
        $response = $this->postJson('/api/cash-flow-items', [
            'name' => '保險費',
            'amount' => 36000,
            'category' => 'living',
            'frequency_type' => 'yearly',
            'start_date' => '2026-02-01',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '保險費',
                    'frequency_type' => 'yearly',
                    'monthly_amount' => '3000.00',
                ],
            ]);
    }

    #[Test]
    public function CanListCashFlowItems(): void
    {
        CashFlowItem::factory()->count(3)->create();

        $response = $this->getJson('/api/cash-flow-items');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'amount', 'amount_display', 'currency',
                        'category', 'category_label',
                        'frequency_type', 'frequency_type_label', 'frequency_display',
                        'monthly_amount', 'is_active',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    #[Test]
    public function CanFilterCashFlowItemsByCategory(): void
    {
        CashFlowItem::factory()->living()->count(2)->create();
        CashFlowItem::factory()->food()->count(1)->create();

        $response = $this->getJson('/api/cash-flow-items?category=living');
        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    #[Test]
    public function CanFilterCashFlowItemsByActiveStatus(): void
    {
        CashFlowItem::factory()->count(2)->create(['is_active' => true]);
        CashFlowItem::factory()->count(1)->create(['is_active' => false]);

        $response = $this->getJson('/api/cash-flow-items?is_active=true');
        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    #[Test]
    public function CanShowCashFlowItem(): void
    {
        $item = CashFlowItem::factory()->create();

        $response = $this->getJson("/api/cash-flow-items/{$item->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $item->id,
                    'name' => $item->name,
                ],
            ]);
    }

    #[Test]
    public function CanUpdateCashFlowItem(): void
    {
        $item = CashFlowItem::factory()->create(['name' => '舊名稱']);

        $response = $this->putJson("/api/cash-flow-items/{$item->id}", [
            'name' => '新名稱',
            'amount' => 30000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '新名稱',
                    'amount' => '30000.00',
                ],
            ]);

        $this->assertDatabaseHas('cash_flow_items', [
            'id' => $item->id,
            'name' => '新名稱',
            'amount' => 30000,
        ]);
    }

    #[Test]
    public function CanDeleteCashFlowItem(): void
    {
        $item = CashFlowItem::factory()->create();

        $response = $this->deleteJson("/api/cash-flow-items/{$item->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '支出估算項目已刪除',
            ]);

        $this->assertDatabaseMissing('cash_flow_items', ['id' => $item->id]);
    }

    #[Test]
    public function ValidatesRequiredFields(): void
    {
        $response = $this->postJson('/api/cash-flow-items', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'amount', 'category', 'frequency_type', 'start_date']);
    }

    #[Test]
    public function ValidatesCategoryEnum(): void
    {
        $response = $this->postJson('/api/cash-flow-items', [
            'name' => '房租',
            'amount' => 25000,
            'category' => 'invalid_category',
            'frequency_type' => 'monthly',
            'start_date' => '2026-02-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    #[Test]
    public function ValidatesFrequencyTypeEnum(): void
    {
        $response = $this->postJson('/api/cash-flow-items', [
            'name' => '房租',
            'amount' => 25000,
            'category' => 'living',
            'frequency_type' => 'invalid_type',
            'start_date' => '2026-02-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frequency_type']);
    }

    #[Test]
    public function PerPageIsLimitedTo100(): void
    {
        CashFlowItem::factory()->count(5)->create();

        $response = $this->getJson('/api/cash-flow-items?per_page=1000');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, $response->json('meta.per_page'));
    }

    #[Test]
    public function CashFlowItemEndpointsRequireAuthentication(): void
    {
        $item = CashFlowItem::factory()->create(['user_id' => $this->user->id]);

        auth()->logout();

        $this->getJson('/api/cash-flow-items')->assertStatus(401);
        $this->postJson('/api/cash-flow-items', [])->assertStatus(401);
        $this->getJson("/api/cash-flow-items/{$item->id}")->assertStatus(401);
        $this->putJson("/api/cash-flow-items/{$item->id}", [])->assertStatus(401);
        $this->deleteJson("/api/cash-flow-items/{$item->id}")->assertStatus(401);
    }

    #[Test]
    public function CannotAccessOtherUsersCashFlowItemById(): void
    {
        $otherUser = \App\Models\User::factory()->create();
        $otherItem = CashFlowItem::factory()->create(['user_id' => $otherUser->id]);

        $this->getJson("/api/cash-flow-items/{$otherItem->id}")->assertStatus(404);
        $this->putJson("/api/cash-flow-items/{$otherItem->id}", ['name' => 'Nope'])->assertStatus(404);
        $this->deleteJson("/api/cash-flow-items/{$otherItem->id}")->assertStatus(404);
    }

    #[Test]
    public function ListCashFlowItemsExcludesOtherUsersData(): void
    {
        $otherUser = \App\Models\User::factory()->create();

        CashFlowItem::factory()->count(2)->create(['user_id' => $this->user->id]);
        CashFlowItem::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/cash-flow-items');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }
}
