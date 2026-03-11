<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\CashFlowFrequencyType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CashFlowFrequencyTypeTest extends TestCase
{
    #[Test]
    public function it_has_three_frequency_types(): void
    {
        $cases = CashFlowFrequencyType::cases();

        $this->assertCount(3, $cases);
    }

    #[Test]
    public function it_has_correct_values(): void
    {
        $this->assertSame('monthly', CashFlowFrequencyType::Monthly->value);
        $this->assertSame('yearly', CashFlowFrequencyType::Yearly->value);
        $this->assertSame('one_time', CashFlowFrequencyType::OneTime->value);
    }

    #[Test]
    #[DataProvider('labelProvider')]
    public function it_returns_correct_labels(CashFlowFrequencyType $type, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $type->label());
    }

    public static function labelProvider(): array
    {
        return [
            'monthly' => [CashFlowFrequencyType::Monthly, '每月'],
            'yearly' => [CashFlowFrequencyType::Yearly, '每年'],
            'one_time' => [CashFlowFrequencyType::OneTime, '一次性'],
        ];
    }

    #[Test]
    public function values_returns_all_string_values(): void
    {
        $values = CashFlowFrequencyType::values();

        $this->assertSame(['monthly', 'yearly', 'one_time'], $values);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $type = CashFlowFrequencyType::from('monthly');

        $this->assertSame(CashFlowFrequencyType::Monthly, $type);
    }

    #[Test]
    public function it_returns_null_for_invalid_value_with_try_from(): void
    {
        $type = CashFlowFrequencyType::tryFrom('invalid');

        $this->assertNull($type);
    }

    #[Test]
    #[DataProvider('monthlyAmountProvider')]
    public function to_monthly_amount_calculates_correctly(
        CashFlowFrequencyType $type,
        string $amount,
        int $interval,
        string $expected
    ): void {
        $this->assertSame($expected, $type->toMonthlyAmount($amount, $interval));
    }

    public static function monthlyAmountProvider(): array
    {
        return [
            'monthly with interval 1' => [CashFlowFrequencyType::Monthly, '10000', 1, '10000.00'],
            'monthly with interval 2' => [CashFlowFrequencyType::Monthly, '10000', 2, '5000.00'],
            'monthly with non-terminating division' => [CashFlowFrequencyType::Monthly, '100.00', 3, '33.33'],
            'yearly with interval 1' => [CashFlowFrequencyType::Yearly, '120000', 1, '10000.00'],
            'yearly with interval 2' => [CashFlowFrequencyType::Yearly, '240000', 2, '10000.00'],
            'yearly with non-terminating division' => [CashFlowFrequencyType::Yearly, '100.00', 3, '2.78'],
            'one_time returns zero' => [CashFlowFrequencyType::OneTime, '50000', 1, '0.00'],
        ];
    }

    #[Test]
    public function to_monthly_amount_handles_zero_interval(): void
    {
        // Should not throw division by zero, should treat as interval 1
        $result = CashFlowFrequencyType::Monthly->toMonthlyAmount('10000', 0);
        $this->assertSame('10000.00', $result);
    }
}
