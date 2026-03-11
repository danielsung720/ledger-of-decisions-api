<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateExpenseRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateExpenseRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new UpdateExpenseRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function ItPassesWithEmptyData(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function ItPassesWithPartialUpdate(): void
    {
        $validator = $this->validate(['amount' => 200]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function AmountMustBeNumeric(): void
    {
        $validator = $this->validate(['amount' => 'not-a-number']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountCannotBeNegative(): void
    {
        $validator = $this->validate(['amount' => -100]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountCanBeZero(): void
    {
        $validator = $this->validate(['amount' => 0]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function CurrencyMustBe3Characters(): void
    {
        $validator = $this->validate(['currency' => 'TWDD']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
    }

    #[Test]
    public function CurrencyCanBeValid(): void
    {
        $validator = $this->validate(['currency' => 'USD']);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[DataProvider('validCategoryProvider')]
    public function CategoryAcceptsValidValues(string $category): void
    {
        $validator = $this->validate(['category' => $category]);

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
        $validator = $this->validate(['category' => 'invalid']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    public function OccurredAtMustBeValidDate(): void
    {
        $validator = $this->validate(['occurred_at' => 'not-a-date']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('occurred_at', $validator->errors()->toArray());
    }

    #[Test]
    public function OccurredAtAcceptsValidDate(): void
    {
        $validator = $this->validate(['occurred_at' => '2026-02-13']);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function OccurredAtAcceptsDatetime(): void
    {
        $validator = $this->validate(['occurred_at' => '2026-02-13 10:30:00']);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function NoteIsOptional(): void
    {
        $validator = $this->validate(['note' => null]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function NoteCannotExceed500Characters(): void
    {
        $validator = $this->validate(['note' => str_repeat('a', 501)]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('note', $validator->errors()->toArray());
    }

    #[Test]
    public function NoteCanBe500Characters(): void
    {
        $validator = $this->validate(['note' => str_repeat('a', 500)]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function ItReturnsChineseErrorMessages(): void
    {
        $validator = $this->validate(['amount' => 'invalid']);

        $errors = $validator->errors()->toArray();

        $this->assertStringContainsString('金額必須是數字', $errors['amount'][0]);
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new UpdateExpenseRequest();

        $this->assertTrue($request->authorize());
    }
}
