<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Intent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IntentTest extends TestCase
{
    #[Test]
    public function it_has_five_intents(): void
    {
        $cases = Intent::cases();

        $this->assertCount(5, $cases);
    }

    #[Test]
    public function it_has_correct_values(): void
    {
        $this->assertSame('necessity', Intent::Necessity->value);
        $this->assertSame('efficiency', Intent::Efficiency->value);
        $this->assertSame('enjoyment', Intent::Enjoyment->value);
        $this->assertSame('recovery', Intent::Recovery->value);
        $this->assertSame('impulse', Intent::Impulse->value);
    }

    #[Test]
    #[DataProvider('labelProvider')]
    public function it_returns_correct_labels(Intent $intent, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $intent->label());
    }

    public static function labelProvider(): array
    {
        return [
            'necessity' => [Intent::Necessity, '必要性'],
            'efficiency' => [Intent::Efficiency, '效率'],
            'enjoyment' => [Intent::Enjoyment, '享受'],
            'recovery' => [Intent::Recovery, '恢復'],
            'impulse' => [Intent::Impulse, '衝動'],
        ];
    }

    #[Test]
    public function values_returns_all_string_values(): void
    {
        $values = Intent::values();

        $this->assertSame(['necessity', 'efficiency', 'enjoyment', 'recovery', 'impulse'], $values);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $intent = Intent::from('impulse');

        $this->assertSame(Intent::Impulse, $intent);
    }

    #[Test]
    public function it_returns_null_for_invalid_value_with_try_from(): void
    {
        $intent = Intent::tryFrom('invalid');

        $this->assertNull($intent);
    }
}
