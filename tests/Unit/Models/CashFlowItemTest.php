<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\CashFlowFrequencyType;
use App\Enums\Category;
use App\Models\CashFlowItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CashFlowItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-02-08');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function ItHasCorrectFillableAttributes(): void
    {
        $item = new CashFlowItem();
        $fillable = $item->getFillable();

        $expectedFillable = [
            'name', 'amount', 'currency', 'category', 'frequency_type',
            'frequency_interval', 'start_date', 'end_date', 'note', 'is_active',
        ];

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    #[Test]
    public function ItHasCorrectDefaultAttributes(): void
    {
        $item = new CashFlowItem();

        $this->assertSame('TWD', $item->currency);
        $this->assertSame(1, $item->frequency_interval);
        $this->assertTrue($item->is_active);
    }

    #[Test]
    public function ItCastsAttributesCorrectly(): void
    {
        $item = CashFlowItem::factory()->create([
            'amount' => 25000.50,
            'category' => Category::Living,
            'frequency_type' => CashFlowFrequencyType::Monthly,
            'start_date' => '2026-02-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->assertSame('25000.50', $item->amount);
        $this->assertInstanceOf(Category::class, $item->category);
        $this->assertInstanceOf(CashFlowFrequencyType::class, $item->frequency_type);
        $this->assertInstanceOf(Carbon::class, $item->start_date);
        $this->assertInstanceOf(Carbon::class, $item->end_date);
        $this->assertTrue($item->is_active);
    }

    #[Test]
    public function GetMonthlyAmountForMonthlyItem(): void
    {
        $item = CashFlowItem::factory()->monthly()->create([
            'amount' => 25000,
            'frequency_interval' => 1,
        ]);

        $this->assertSame('25000.00', $item->getMonthlyAmount());
    }

    #[Test]
    public function GetMonthlyAmountForYearlyItem(): void
    {
        $item = CashFlowItem::factory()->yearly()->create([
            'amount' => 36000,
            'frequency_interval' => 1,
        ]);

        $this->assertSame('3000.00', $item->getMonthlyAmount());
    }

    #[Test]
    public function GetMonthlyAmountForOneTimeReturnsZero(): void
    {
        $item = CashFlowItem::factory()->oneTime()->create([
            'amount' => 10000,
        ]);

        $this->assertSame('0.00', $item->getMonthlyAmount());
    }

    #[Test]
    public function IsActiveForMonthReturnsCorrectValues(): void
    {
        $item = CashFlowItem::factory()->create([
            'start_date' => '2026-02-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        // Before start
        $this->assertFalse($item->isActiveForMonth(Carbon::parse('2026-01-01')));

        // In range
        $this->assertTrue($item->isActiveForMonth(Carbon::parse('2026-03-01')));

        // After end
        $this->assertFalse($item->isActiveForMonth(Carbon::parse('2026-07-01')));
    }

    #[Test]
    public function ScopeActiveReturnsOnlyActiveRecords(): void
    {
        CashFlowItem::factory()->create(['is_active' => true]);
        CashFlowItem::factory()->create(['is_active' => true]);
        CashFlowItem::factory()->create(['is_active' => false]);

        $activeCount = CashFlowItem::active()->count();

        $this->assertSame(2, $activeCount);
    }

    #[Test]
    public function ScopeInCategoryFiltersByCategory(): void
    {
        CashFlowItem::factory()->living()->create();
        CashFlowItem::factory()->living()->create();
        CashFlowItem::factory()->food()->create();

        $livingCount = CashFlowItem::inCategory('living')->count();
        $foodCount = CashFlowItem::inCategory('food')->count();

        $this->assertSame(2, $livingCount);
        $this->assertSame(1, $foodCount);
    }

    #[Test]
    public function ScopeInCategoryAcceptsArray(): void
    {
        CashFlowItem::factory()->living()->create();
        CashFlowItem::factory()->food()->create();
        CashFlowItem::factory()->create(['category' => Category::Transport]);

        $count = CashFlowItem::inCategory(['living', 'food'])->count();

        $this->assertSame(2, $count);
    }

    #[Test]
    public function ScopeValidForPeriodReturnsValidRecords(): void
    {
        // Valid record
        CashFlowItem::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => null,
            'is_active' => true,
        ]);

        // Invalid: inactive
        CashFlowItem::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => null,
            'is_active' => false,
        ]);

        // Invalid: starts after period
        CashFlowItem::factory()->create([
            'start_date' => '2026-04-01',
            'end_date' => null,
            'is_active' => true,
        ]);

        $validCount = CashFlowItem::validForPeriod(
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28')
        )->count();

        $this->assertSame(1, $validCount);
    }

    #[Test]
    public function GetAmountForMonthForOneTimeOnlyAppliesToStartMonth(): void
    {
        $item = CashFlowItem::factory()->oneTime()->create([
            'amount' => 10000,
            'start_date' => '2026-02-10',
            'is_active' => true,
        ]);

        $this->assertSame('10000.00', $item->getAmountForMonth(Carbon::parse('2026-02-01')));
        $this->assertSame('0.00', $item->getAmountForMonth(Carbon::parse('2026-03-01')));
    }

    #[Test]
    public function GetAmountForMonthForYearlyOnlyAppliesToMatchingMonth(): void
    {
        $item = CashFlowItem::factory()->yearly()->create([
            'amount' => 36000,
            'frequency_interval' => 1,
            'start_date' => '2026-02-10',
            'is_active' => true,
        ]);

        $this->assertSame('36000.00', $item->getAmountForMonth(Carbon::parse('2026-02-01')));
        $this->assertSame('0.00', $item->getAmountForMonth(Carbon::parse('2026-03-01')));
    }

    #[Test]
    public function GetAmountForMonthRoundsNonTerminatingDivisionForMonthlyType(): void
    {
        $item = CashFlowItem::factory()->monthly()->create([
            'amount' => 100,
            'frequency_interval' => 3,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->assertSame('33.33', $item->getAmountForMonth(Carbon::parse('2026-02-01')));
    }

    #[Test]
    public function GetAmountForMonthRoundsNonTerminatingDivisionForYearlyType(): void
    {
        $item = CashFlowItem::factory()->yearly()->create([
            'amount' => 100,
            'frequency_interval' => 3,
            'start_date' => '2026-02-01',
            'is_active' => true,
        ]);

        $this->assertSame('33.33', $item->getAmountForMonth(Carbon::parse('2026-02-01')));
    }

    #[Test]
    public function IsActiveForMonthReturnsTrueWhenNoEndDateAndStarted(): void
    {
        $item = CashFlowItem::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => null,
            'is_active' => true,
        ]);

        $this->assertTrue($item->isActiveForMonth(Carbon::parse('2026-06-01')));
    }

    #[Test]
    public function IsActiveForMonthReturnsFalseWhenItemIsInactive(): void
    {
        $item = CashFlowItem::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => false,
        ]);

        $this->assertFalse($item->isActiveForMonth(Carbon::parse('2026-06-01')));
    }

    #[Test]
    public function GetAmountForMonthForMonthlyTypeReturnsAmountDividedByInterval(): void
    {
        $item = CashFlowItem::factory()->monthly()->create([
            'amount' => 12000,
            'frequency_interval' => 3,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->assertSame('4000.00', $item->getAmountForMonth(Carbon::parse('2026-02-01')));
    }

    #[Test]
    public function GetAmountForMonthReturnsZeroWhenItemIsNotActiveForRequestedMonth(): void
    {
        $item = CashFlowItem::factory()->monthly()->create([
            'amount' => 12000,
            'frequency_interval' => 1,
            'start_date' => '2026-06-01',
            'is_active' => true,
        ]);

        $this->assertSame('0.00', $item->getAmountForMonth(Carbon::parse('2026-02-01')));
    }
}
