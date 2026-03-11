<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Enums\Category;
use App\Http\Requests\StoreExpenseRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreExpenseRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreExpenseRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function ItPassesWithValidData(): void
    {
        $validator = $this->validate([
            'amount' => 100.50,
            'category' => 'food',
            'occurred_at' => '2026-02-08',
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function ItPassesWithAllFields(): void
    {
        $validator = $this->validate([
            'amount' => 100.50,
            'currency' => 'USD',
            'category' => 'food',
            'occurred_at' => '2026-02-08 10:00:00',
            'note' => 'Test expense note',
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function AmountIsRequired(): void
    {
        $validator = $this->validate([
            'category' => 'food',
            'occurred_at' => '2026-02-08',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountMustBeNumeric(): void
    {
        $validator = $this->validate([
            'amount' => 'not-a-number',
            'category' => 'food',
            'occurred_at' => '2026-02-08',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountMustBeNonNegative(): void
    {
        $validator = $this->validate([
            'amount' => -100,
            'category' => 'food',
            'occurred_at' => '2026-02-08',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountCanBeZero(): void
    {
        $validator = $this->validate([
            'amount' => 0,
            'category' => 'food',
            'occurred_at' => '2026-02-08',
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function CategoryIsRequired(): void
    {
        $validator = $this->validate([
            'amount' => 100,
            'occurred_at' => '2026-02-08',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    #[DataProvider('validCategoryProvider')]
    public function CategoryAcceptsValidValues(string $category): void
    {
        $validator = $this->validate([
            'amount' => 100,
            'category' => $category,
            'occurred_at' => '2026-02-08',
        ]);

        $this->assertTrue($validator->passes());
    }

    public static function validCategoryProvider(): array
    {
        return [
            'food' => ['food'],
            'transport' => ['transport'],
            'training' => ['training'],
            'living' => ['living'],
            'other' => ['other'],
        ];
    }

    #[Test]
    public function CategoryRejectsInvalidValues(): void
    {
        $validator = $this->validate([
            'amount' => 100,
            'category' => 'invalid-category',
            'occurred_at' => '2026-02-08',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    public function OccurredAtIsRequired(): void
    {
        $validator = $this->validate([
            'amount' => 100,
            'category' => 'food',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('occurred_at', $validator->errors()->toArray());
    }

    #[Test]
    public function OccurredAtMustBeValidDate(): void
    {
        $validator = $this->validate([
            'amount' => 100,
            'category' => 'food',
            'occurred_at' => 'not-a-date',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('occurred_at', $validator->errors()->toArray());
    }

    #[Test]
    public function CurrencyMustBe3Characters(): void
    {
        $validator = $this->validate([
            'amount' => 100,
            'category' => 'food',
            'occurred_at' => '2026-02-08',
            'currency' => 'USDD',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
    }

    #[Test]
    public function CurrencyIsOptional(): void
    {
        $validator = $this->validate([
            'amount' => 100,
            'category' => 'food',
            'occurred_at' => '2026-02-08',
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function NoteIsOptional(): void
    {
        $validator = $this->validate([
            'amount' => 100,
            'category' => 'food',
            'occurred_at' => '2026-02-08',
            'note' => null,
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function NoteCannotExceed500Characters(): void
    {
        $validator = $this->validate([
            'amount' => 100,
            'category' => 'food',
            'occurred_at' => '2026-02-08',
            'note' => str_repeat('a', 501),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('note', $validator->errors()->toArray());
    }

    #[Test]
    public function NoteCanBe500Characters(): void
    {
        $validator = $this->validate([
            'amount' => 100,
            'category' => 'food',
            'occurred_at' => '2026-02-08',
            'note' => str_repeat('a', 500),
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function ItReturnsChineseErrorMessages(): void
    {
        $validator = $this->validate([]);

        $errors = $validator->errors()->toArray();

        $this->assertStringContainsString('請輸入消費金額', $errors['amount'][0]);
        $this->assertStringContainsString('請選擇消費類別', $errors['category'][0]);
        $this->assertStringContainsString('請輸入消費時間', $errors['occurred_at'][0]);
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new StoreExpenseRequest();

        $this->assertTrue($request->authorize());
    }
}
