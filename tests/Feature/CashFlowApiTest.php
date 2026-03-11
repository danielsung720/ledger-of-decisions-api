<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CashFlowItem;
use App\Models\Income;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class CashFlowApiTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        Carbon::setTestNow('2026-02-08');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function can_get_cash_flow_summary(): void
    {
        Income::factory()->monthly()->create([
            'amount' => 80000,
            'frequency_interval' => 1,
            'is_active' => true,
        ]);

        CashFlowItem::factory()->monthly()->create([
            'amount' => 25000,
            'frequency_interval' => 1,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/cash-flow/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_income' => '80000.00',
                    'total_expense' => '25000.00',
                    'net_cash_flow' => '55000.00',
                ],
            ]);

        $savingsRate = (float) $response->json('data.savings_rate');
        $this->assertEqualsWithDelta(68.75, $savingsRate, 0.1);
    }

    #[Test]
    public function summary_only_includes_active_items(): void
    {
        Income::factory()->monthly()->create([
            'amount' => 80000,
            'frequency_interval' => 1,
            'is_active' => true,
        ]);

        Income::factory()->monthly()->create([
            'amount' => 20000,
            'frequency_interval' => 1,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/cash-flow/summary');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_income', '80000.00');
    }

    #[Test]
    public function summary_calculates_yearly_income_correctly(): void
    {
        Income::factory()->yearly()->create([
            'amount' => 120000,
            'frequency_interval' => 1,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/cash-flow/summary');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_income', '10000.00');
    }

    #[Test]
    public function summary_returns_zero_savings_rate_when_no_income(): void
    {
        CashFlowItem::factory()->monthly()->create([
            'amount' => 25000,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/cash-flow/summary');

        $response->assertStatus(200)
            ->assertJsonPath('data.savings_rate', '0.0');
    }

    #[Test]
    public function can_get_cash_flow_projection(): void
    {
        Income::factory()->monthly()->create([
            'amount' => 80000,
            'frequency_interval' => 1,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);

        CashFlowItem::factory()->monthly()->create([
            'amount' => 50000,
            'frequency_interval' => 1,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/cash-flow/projection?months=3');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'month',
                        'income',
                        'expense',
                        'net',
                        'cumulative_balance',
                    ],
                ],
            ]);

        $firstMonth = $response->json('data.0');
        $this->assertSame('2026/02', $firstMonth['month']);
        $this->assertSame('80000.00', $firstMonth['income']);
        $this->assertSame('50000.00', $firstMonth['expense']);
        $this->assertSame('30000.00', $firstMonth['net']);
        $this->assertSame('30000.00', $firstMonth['cumulative_balance']);

        $thirdMonth = $response->json('data.2');
        $this->assertSame('2026/04', $thirdMonth['month']);
        $this->assertSame('90000.00', $thirdMonth['cumulative_balance']);
    }

    #[Test]
    public function projection_defaults_to_one_month(): void
    {
        Income::factory()->monthly()->create(['is_active' => true]);

        $response = $this->getJson('/api/cash-flow/projection');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function projection_is_limited_to12_months(): void
    {
        Income::factory()->monthly()->create(['is_active' => true]);

        $response = $this->getJson('/api/cash-flow/projection?months=24');

        $response->assertStatus(200)
            ->assertJsonCount(12, 'data');
    }

    #[Test]
    public function projection_handles_yearly_income(): void
    {
        Income::factory()->yearly()->create([
            'amount' => 120000,
            'frequency_interval' => 1,
            'start_date' => '2026-02-01',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/cash-flow/projection?months=3');

        $response->assertStatus(200);
        $this->assertSame('120000.00', $response->json('data.0.income'));
        $this->assertSame('0.00', $response->json('data.1.income'));
    }

    #[Test]
    public function projection_handles_one_time_income(): void
    {
        Income::factory()->oneTime()->create([
            'amount' => 50000,
            'start_date' => '2026-02-15',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/cash-flow/projection?months=2');

        $response->assertStatus(200);
        $this->assertSame('50000.00', $response->json('data.0.income'));
        $this->assertSame('0.00', $response->json('data.1.income'));
    }

    #[Test]
    public function projection_handles_empty_data(): void
    {
        $response = $this->getJson('/api/cash-flow/projection?months=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.income', '0.00')
            ->assertJsonPath('data.0.expense', '0.00')
            ->assertJsonPath('data.0.net', '0.00')
            ->assertJsonPath('data.0.cumulative_balance', '0.00');
    }

    #[Test]
    public function projection_shows_correct_month_format(): void
    {
        Income::factory()->monthly()->create(['is_active' => true]);

        $response = $this->getJson('/api/cash-flow/projection?months=1');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.month', '2026/02');
    }

    #[Test]
    public function projection_rejects_non_integer_months(): void
    {
        $response = $this->getJson('/api/cash-flow/projection?months=abc');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', '預測月數必須是整數');
    }

    #[Test]
    public function projection_rejects_months_smaller_than_one(): void
    {
        $response = $this->getJson('/api/cash-flow/projection?months=0');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', '預測月數至少為 1');
    }

    #[Test]
    public function summary_should_exclude_other_user_data(): void
    {
        $otherUser = User::factory()->create();

        Income::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 100000,
            'is_active' => true,
        ]);
        CashFlowItem::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 30000,
            'is_active' => true,
        ]);

        Income::factory()->monthly()->create([
            'user_id' => $otherUser->id,
            'amount' => 999999,
            'is_active' => true,
        ]);
        CashFlowItem::factory()->monthly()->create([
            'user_id' => $otherUser->id,
            'amount' => 888888,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/cash-flow/summary');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_income', '100000.00')
            ->assertJsonPath('data.total_expense', '30000.00')
            ->assertJsonPath('data.net_cash_flow', '70000.00');
    }

    #[Test]
    public function projection_should_exclude_other_user_data(): void
    {
        $otherUser = User::factory()->create();

        Income::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 70000,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);
        CashFlowItem::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 20000,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);

        Income::factory()->monthly()->create([
            'user_id' => $otherUser->id,
            'amount' => 300000,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);
        CashFlowItem::factory()->monthly()->create([
            'user_id' => $otherUser->id,
            'amount' => 100000,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/cash-flow/projection?months=2');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.month', '2026/02')
            ->assertJsonPath('data.0.income', '70000.00')
            ->assertJsonPath('data.0.expense', '20000.00')
            ->assertJsonPath('data.0.net', '50000.00')
            ->assertJsonPath('data.1.cumulative_balance', '100000.00');
    }

    #[Test]
    public function cash_flow_endpoints_require_authentication(): void
    {
        auth()->guard('web')->logout();

        $this->getJson('/api/cash-flow/summary')->assertStatus(401);
        $this->getJson('/api/cash-flow/projection')->assertStatus(401);
    }
}
