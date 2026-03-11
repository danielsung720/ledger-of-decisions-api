<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\FrequencyType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FrequencyTypeTest extends TestCase
{
    #[Test]
    public function it_has_four_frequency_types(): void
    {
        $cases = FrequencyType::cases();

        $this->assertCount(4, $cases);
    }

    #[Test]
    public function it_has_correct_values(): void
    {
        $this->assertSame('daily', FrequencyType::Daily->value);
        $this->assertSame('weekly', FrequencyType::Weekly->value);
        $this->assertSame('monthly', FrequencyType::Monthly->value);
        $this->assertSame('yearly', FrequencyType::Yearly->value);
    }

    #[Test]
    #[DataProvider('labelProvider')]
    public function it_returns_correct_labels(FrequencyType $type, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $type->label());
    }

    public static function labelProvider(): array
    {
        return [
            'daily' => [FrequencyType::Daily, '每日'],
            'weekly' => [FrequencyType::Weekly, '每週'],
            'monthly' => [FrequencyType::Monthly, '每月'],
            'yearly' => [FrequencyType::Yearly, '每年'],
        ];
    }

    #[Test]
    public function values_returns_all_string_values(): void
    {
        $values = FrequencyType::values();

        $this->assertSame(['daily', 'weekly', 'monthly', 'yearly'], $values);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $type = FrequencyType::from('monthly');

        $this->assertSame(FrequencyType::Monthly, $type);
    }

    #[Test]
    public function it_returns_null_for_invalid_value_with_try_from(): void
    {
        $type = FrequencyType::tryFrom('invalid');

        $this->assertNull($type);
    }
}
