<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ConfidenceLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConfidenceLevelTest extends TestCase
{
    #[Test]
    public function it_has_three_levels(): void
    {
        $cases = ConfidenceLevel::cases();

        $this->assertCount(3, $cases);
    }

    #[Test]
    public function it_has_correct_values(): void
    {
        $this->assertSame('high', ConfidenceLevel::High->value);
        $this->assertSame('medium', ConfidenceLevel::Medium->value);
        $this->assertSame('low', ConfidenceLevel::Low->value);
    }

    #[Test]
    #[DataProvider('labelProvider')]
    public function it_returns_correct_labels(ConfidenceLevel $level, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $level->label());
    }

    public static function labelProvider(): array
    {
        return [
            'high' => [ConfidenceLevel::High, '高'],
            'medium' => [ConfidenceLevel::Medium, '中'],
            'low' => [ConfidenceLevel::Low, '低'],
        ];
    }

    #[Test]
    public function values_returns_all_string_values(): void
    {
        $values = ConfidenceLevel::values();

        $this->assertSame(['high', 'medium', 'low'], $values);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $level = ConfidenceLevel::from('high');

        $this->assertSame(ConfidenceLevel::High, $level);
    }

    #[Test]
    public function it_returns_null_for_invalid_value_with_try_from(): void
    {
        $level = ConfidenceLevel::tryFrom('invalid');

        $this->assertNull($level);
    }
}
