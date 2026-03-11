<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Income;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class IncomeApiTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
    }

    #[Test]
    public function CanCreateIncome(): void
    {
        $response = $this->postJson('/api/incomes', [
            'name' => '薪資',
            'amount' => 80000,
            'frequency_type' => 'monthly',
            'frequency_interval' => 1,
            'start_date' => '2026-02-01',
            'note' => '本薪',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '薪資',
                    'amount' => '80000.00',
                    'frequency_type' => 'monthly',
                    'frequency_type_label' => '每月',
                    'frequency_interval' => 1,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('incomes', [
            'name' => '薪資',
            'amount' => 80000,
            'frequency_type' => 'monthly',
        ]);
    }

    #[Test]
    public function CanCreateYearlyIncome(): void
    {
        $response = $this->postJson('/api/incomes', [
            'name' => '年終獎金',
            'amount' => 160000,
            'frequency_type' => 'yearly',
            'start_date' => '2026-02-01',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '年終獎金',
                    'frequency_type' => 'yearly',
                    'frequency_type_label' => '每年',
                ],
            ]);
    }

    #[Test]
    public function CanCreateOneTimeIncome(): void
    {
        $response = $this->postJson('/api/incomes', [
            'name' => '出售舊車',
            'amount' => 300000,
            'frequency_type' => 'one_time',
            'start_date' => '2026-03-15',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '出售舊車',
                    'frequency_type' => 'one_time',
                    'frequency_type_label' => '一次性',
                ],
            ]);
    }

    #[Test]
    public function CanListIncomes(): void
    {
        Income::factory()->count(3)->create();

        $response = $this->getJson('/api/incomes');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'amount', 'amount_display', 'currency',
                        'frequency_type', 'frequency_type_label', 'frequency_display',
                        'monthly_amount', 'is_active',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    #[Test]
    public function CanFilterIncomesByActiveStatus(): void
    {
        Income::factory()->count(2)->create(['is_active' => true]);
        Income::factory()->count(1)->create(['is_active' => false]);

        $response = $this->getJson('/api/incomes?is_active=true');
        $response->assertStatus(200)->assertJsonCount(2, 'data');

        $response = $this->getJson('/api/incomes?is_active=false');
        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    #[Test]
    public function CanFilterIncomesByFrequencyType(): void
    {
        Income::factory()->monthly()->count(2)->create();
        Income::factory()->yearly()->count(1)->create();

        $response = $this->getJson('/api/incomes?frequency_type=monthly');
        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    #[Test]
    public function CanShowIncome(): void
    {
        $income = Income::factory()->create();

        $response = $this->getJson("/api/incomes/{$income->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $income->id,
                    'name' => $income->name,
                ],
            ]);
    }

    #[Test]
    public function CanUpdateIncome(): void
    {
        $income = Income::factory()->create(['name' => '舊名稱']);

        $response = $this->putJson("/api/incomes/{$income->id}", [
            'name' => '新名稱',
            'amount' => 85000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => '新名稱',
                    'amount' => '85000.00',
                ],
            ]);

        $this->assertDatabaseHas('incomes', [
            'id' => $income->id,
            'name' => '新名稱',
            'amount' => 85000,
        ]);
    }

    #[Test]
    public function CanDeleteIncome(): void
    {
        $income = Income::factory()->create();

        $response = $this->deleteJson("/api/incomes/{$income->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '收入已刪除',
            ]);

        $this->assertDatabaseMissing('incomes', ['id' => $income->id]);
    }

    #[Test]
    public function ValidatesRequiredFields(): void
    {
        $response = $this->postJson('/api/incomes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'amount', 'frequency_type', 'start_date']);
    }

    #[Test]
    public function ValidatesFrequencyTypeEnum(): void
    {
        $response = $this->postJson('/api/incomes', [
            'name' => '薪資',
            'amount' => 80000,
            'frequency_type' => 'invalid_type',
            'start_date' => '2026-02-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['frequency_type']);
    }

    #[Test]
    public function ValidatesEndDateMustBeAfterStartDate(): void
    {
        $response = $this->postJson('/api/incomes', [
            'name' => '薪資',
            'amount' => 80000,
            'frequency_type' => 'monthly',
            'start_date' => '2026-02-01',
            'end_date' => '2025-12-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    #[Test]
    public function MonthlyAmountIsCalculatedCorrectly(): void
    {
        $response = $this->postJson('/api/incomes', [
            'name' => '年終獎金',
            'amount' => 120000,
            'frequency_type' => 'yearly',
            'frequency_interval' => 1,
            'start_date' => '2026-02-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.monthly_amount', '10000.00');
    }

    #[Test]
    public function PerPageIsLimitedTo100(): void
    {
        Income::factory()->count(5)->create();

        $response = $this->getJson('/api/incomes?per_page=1000');

        // Should be clamped to 100 max
        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, $response->json('meta.per_page'));
    }

    #[Test]
    public function IncomeEndpointsRequireAuthentication(): void
    {
        $income = Income::factory()->create(['user_id' => $this->user->id]);

        auth()->logout();

        $this->getJson('/api/incomes')->assertStatus(401);
        $this->postJson('/api/incomes', [])->assertStatus(401);
        $this->getJson("/api/incomes/{$income->id}")->assertStatus(401);
        $this->putJson("/api/incomes/{$income->id}", [])->assertStatus(401);
        $this->deleteJson("/api/incomes/{$income->id}")->assertStatus(401);
    }

    #[Test]
    public function CannotAccessOtherUsersIncomeById(): void
    {
        $otherUser = User::factory()->create();
        $otherIncome = Income::factory()->create(['user_id' => $otherUser->id]);

        $this->getJson("/api/incomes/{$otherIncome->id}")->assertStatus(404);
        $this->putJson("/api/incomes/{$otherIncome->id}", ['name' => 'Nope'])->assertStatus(404);
        $this->deleteJson("/api/incomes/{$otherIncome->id}")->assertStatus(404);
    }

    #[Test]
    public function ListIncomesExcludesOtherUsersData(): void
    {
        $otherUser = User::factory()->create();

        Income::factory()->count(2)->create(['user_id' => $this->user->id]);
        Income::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/incomes');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
