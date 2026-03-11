<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\CashFlowFrequencyType;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IncomeTest extends TestCase
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
        $income = new Income();
        $fillable = $income->getFillable();

        $expectedFillable = [
            'name', 'amount', 'currency', 'frequency_type',
            'frequency_interval', 'start_date', 'end_date', 'note', 'is_active',
        ];

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    #[Test]
    public function ItHasCorrectDefaultAttributes(): void
    {
        $income = new Income();

        $this->assertSame('TWD', $income->currency);
        $this->assertSame(1, $income->frequency_interval);
        $this->assertTrue($income->is_active);
    }

    #[Test]
    public function ItCastsAttributesCorrectly(): void
    {
        $income = Income::factory()->create([
            'amount' => 80000.50,
            'frequency_type' => CashFlowFrequencyType::Monthly,
            'start_date' => '2026-02-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->assertSame('80000.50', $income->amount);
        $this->assertInstanceOf(CashFlowFrequencyType::class, $income->frequency_type);
        $this->assertInstanceOf(Carbon::class, $income->start_date);
        $this->assertInstanceOf(Carbon::class, $income->end_date);
        $this->assertTrue($income->is_active);
    }

    #[Test]
    public function GetMonthlyAmountForMonthlyIncome(): void
    {
        $income = Income::factory()->monthly()->create([
            'amount' => 80000,
            'frequency_interval' => 1,
        ]);

        $this->assertSame('80000.00', $income->getMonthlyAmount());
    }

    #[Test]
    public function GetMonthlyAmountForMonthlyWithInterval(): void
    {
        $income = Income::factory()->monthly()->create([
            'amount' => 20000,
            'frequency_interval' => 2,
        ]);

        $this->assertSame('10000.00', $income->getMonthlyAmount());
    }

    #[Test]
    public function GetMonthlyAmountForYearlyIncome(): void
    {
        $income = Income::factory()->yearly()->create([
            'amount' => 120000,
            'frequency_interval' => 1,
        ]);

        $this->assertSame('10000.00', $income->getMonthlyAmount());
    }

    #[Test]
    public function GetMonthlyAmountForOneTimeReturnsZero(): void
    {
        $income = Income::factory()->oneTime()->create([
            'amount' => 50000,
        ]);

        $this->assertSame('0.00', $income->getMonthlyAmount());
    }

    #[Test]
    public function IsActiveForMonthReturnsFalseWhenInactive(): void
    {
        $income = Income::factory()->inactive()->create([
            'start_date' => '2026-01-01',
        ]);

        $this->assertFalse($income->isActiveForMonth(Carbon::parse('2026-02-01')));
    }

    #[Test]
    public function IsActiveForMonthReturnsFalseWhenStartDateIsAfterMonth(): void
    {
        $income = Income::factory()->create([
            'start_date' => '2026-03-01',
        ]);

        $this->assertFalse($income->isActiveForMonth(Carbon::parse('2026-02-01')));
    }

    #[Test]
    public function IsActiveForMonthReturnsFalseWhenEndDateIsBeforeMonth(): void
    {
        $income = Income::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ]);

        $this->assertFalse($income->isActiveForMonth(Carbon::parse('2026-02-01')));
    }

    #[Test]
    public function IsActiveForMonthReturnsTrueWhenActiveInRange(): void
    {
        $income = Income::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => null,
            'is_active' => true,
        ]);

        $this->assertTrue($income->isActiveForMonth(Carbon::parse('2026-02-01')));
    }

    #[Test]
    public function GetAmountForMonthReturnsMonthlyAmountForMonthlyIncome(): void
    {
        $income = Income::factory()->monthly()->create([
            'amount' => 80000,
            'frequency_interval' => 1,
            'start_date' => '2026-01-01',
        ]);

        $this->assertSame('80000.00', $income->getAmountForMonth(Carbon::parse('2026-02-01')));
    }

    #[Test]
    public function GetAmountForMonthRoundsNonTerminatingDivisionForMonthlyIncome(): void
    {
        $income = Income::factory()->monthly()->create([
            'amount' => 100,
            'frequency_interval' => 3,
            'start_date' => '2026-01-01',
        ]);

        $this->assertSame('33.33', $income->getAmountForMonth(Carbon::parse('2026-02-01')));
    }

    #[Test]
    public function GetAmountForMonthReturnsZeroForInactiveMonth(): void
    {
        $income = Income::factory()->monthly()->create([
            'amount' => 80000,
            'start_date' => '2026-03-01',
        ]);

        $this->assertSame('0.00', $income->getAmountForMonth(Carbon::parse('2026-02-01')));
    }

    #[Test]
    public function GetAmountForMonthForOneTimeOnlyAppliesToStartMonth(): void
    {
        $income = Income::factory()->oneTime()->create([
            'amount' => 50000,
            'start_date' => '2026-02-15',
        ]);

        // Should return amount in start month
        $this->assertSame('50000.00', $income->getAmountForMonth(Carbon::parse('2026-02-01')));

        // Should return zero in other months
        $this->assertSame('0.00', $income->getAmountForMonth(Carbon::parse('2026-03-01')));
    }

    #[Test]
    public function GetAmountForMonthForYearlyOnlyAppliesToMatchingMonth(): void
    {
        $income = Income::factory()->yearly()->create([
            'amount' => 120000,
            'frequency_interval' => 1,
            'start_date' => '2026-02-01',
        ]);

        // Should return amount in February (month of start_date)
        $this->assertSame('120000.00', $income->getAmountForMonth(Carbon::parse('2026-02-01')));

        // Should return zero in other months
        $this->assertSame('0.00', $income->getAmountForMonth(Carbon::parse('2026-03-01')));
    }

    #[Test]
    public function GetAmountForMonthRoundsNonTerminatingDivisionForYearlyIncome(): void
    {
        $income = Income::factory()->yearly()->create([
            'amount' => 100,
            'frequency_interval' => 3,
            'start_date' => '2026-02-01',
        ]);

        $this->assertSame('33.33', $income->getAmountForMonth(Carbon::parse('2026-02-01')));
    }

    #[Test]
    public function ScopeActiveReturnsOnlyActiveRecords(): void
    {
        Income::factory()->create(['is_active' => true]);
        Income::factory()->create(['is_active' => true]);
        Income::factory()->create(['is_active' => false]);

        $activeCount = Income::active()->count();

        $this->assertSame(2, $activeCount);
    }

    #[Test]
    public function ScopeValidForPeriodReturnsValidRecords(): void
    {
        // Valid: starts before period, no end date
        Income::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => null,
            'is_active' => true,
        ]);

        // Valid: ends after period starts
        Income::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
        ]);

        // Invalid: starts after period ends
        Income::factory()->create([
            'start_date' => '2026-04-01',
            'end_date' => null,
            'is_active' => true,
        ]);

        // Invalid: ends before period starts
        Income::factory()->create([
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
        ]);

        // Invalid: inactive
        Income::factory()->create([
            'start_date' => '2026-01-01',
            'end_date' => null,
            'is_active' => false,
        ]);

        $validCount = Income::validForPeriod(
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28')
        )->count();

        $this->assertSame(2, $validCount);
    }
}
