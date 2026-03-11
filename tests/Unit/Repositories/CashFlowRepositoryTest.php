<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\DTO\CashFlow\CashFlowProjectionFiltersDto;
use App\DTO\CashFlow\CashFlowProjectionQueryDto;
use App\DTO\CashFlow\CashFlowSummaryQueryDto;
use App\Models\CashFlowItem;
use App\Models\Income;
use App\Models\User;
use App\Repositories\CashFlowRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class CashFlowRepositoryTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    private CashFlowRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        $this->repository = new CashFlowRepository;
        Carbon::setTestNow('2026-02-08');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function get_summary_amounts_should_apply_scope_and_active_filter(): void
    {
        $otherUser = User::factory()->create();

        Income::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 80000,
            'is_active' => true,
        ]);
        Income::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 50000,
            'is_active' => false,
        ]);
        Income::factory()->monthly()->create([
            'user_id' => $otherUser->id,
            'amount' => 90000,
            'is_active' => true,
        ]);

        CashFlowItem::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 25000,
            'is_active' => true,
        ]);
        CashFlowItem::factory()->monthly()->create([
            'user_id' => $otherUser->id,
            'amount' => 99999,
            'is_active' => true,
        ]);

        $amounts = $this->repository->getSummaryAmounts(
            CashFlowSummaryQueryDto::forUser((int) $this->user->id)
        );

        $this->assertSame(80000.0, $amounts->totalIncome);
        $this->assertSame(25000.0, $amounts->totalExpense);
    }

    #[Test]
    public function get_projection_month_amounts_should_calculate_per_month_values(): void
    {
        Income::factory()->yearly()->create([
            'user_id' => $this->user->id,
            'amount' => 120000,
            'frequency_interval' => 1,
            'start_date' => '2026-02-01',
            'is_active' => true,
        ]);
        Income::factory()->oneTime()->create([
            'user_id' => $this->user->id,
            'amount' => 50000,
            'start_date' => '2026-02-15',
            'is_active' => true,
        ]);

        CashFlowItem::factory()->monthly()->create([
            'user_id' => $this->user->id,
            'amount' => 30000,
            'frequency_interval' => 1,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $rows = $this->repository->getProjectionMonthAmounts(
            CashFlowProjectionQueryDto::forUser(
                (int) $this->user->id,
                new CashFlowProjectionFiltersDto(months: 3)
            ),
            Carbon::parse('2026-02-01')
        );

        $this->assertCount(3, $rows);
        $this->assertSame('2026/02', $rows[0]->month);
        $this->assertSame(170000.0, $rows[0]->income);
        $this->assertSame(30000.0, $rows[0]->expense);

        $this->assertSame('2026/03', $rows[1]->month);
        $this->assertSame(0.0, $rows[1]->income);
        $this->assertSame(30000.0, $rows[1]->expense);

        $this->assertSame('2026/04', $rows[2]->month);
        $this->assertSame(0.0, $rows[2]->income);
        $this->assertSame(30000.0, $rows[2]->expense);
    }
}
