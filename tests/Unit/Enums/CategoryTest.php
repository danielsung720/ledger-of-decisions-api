<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Category;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    #[Test]
    public function it_has_five_categories(): void
    {
        $cases = Category::cases();

        $this->assertCount(5, $cases);
    }

    #[Test]
    public function it_has_correct_values(): void
    {
        $this->assertSame('food', Category::Food->value);
        $this->assertSame('transport', Category::Transport->value);
        $this->assertSame('training', Category::Training->value);
        $this->assertSame('living', Category::Living->value);
        $this->assertSame('other', Category::Other->value);
    }

    #[Test]
    #[DataProvider('labelProvider')]
    public function it_returns_correct_labels(Category $category, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $category->label());
    }

    public static function labelProvider(): array
    {
        return [
            'food' => [Category::Food, '飲食'],
            'transport' => [Category::Transport, '交通'],
            'training' => [Category::Training, '學習/訓練'],
            'living' => [Category::Living, '生活'],
            'other' => [Category::Other, '其他'],
        ];
    }

    #[Test]
    public function values_returns_all_string_values(): void
    {
        $values = Category::values();

        $this->assertSame(['food', 'transport', 'training', 'living', 'other'], $values);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $category = Category::from('food');

        $this->assertSame(Category::Food, $category);
    }

    #[Test]
    public function it_returns_null_for_invalid_value_with_try_from(): void
    {
        $category = Category::tryFrom('invalid');

        $this->assertNull($category);
    }
}
