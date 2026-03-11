<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateCashFlowItemRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateCashFlowItemRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new UpdateCashFlowItemRequest();

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
        $validator = $this->validate(['name' => '新項目名稱']);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function NameCannotExceed255Characters(): void
    {
        $validator = $this->validate(['name' => str_repeat('a', 256)]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
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
    public function CurrencyMustBe3Characters(): void
    {
        $validator = $this->validate(['currency' => 'TWDD']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
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
    #[DataProvider('validFrequencyTypeProvider')]
    public function FrequencyTypeAcceptsValidValues(string $frequencyType): void
    {
        $validator = $this->validate(['frequency_type' => $frequencyType]);

        $this->assertTrue($validator->passes());
    }

    public static function validFrequencyTypeProvider(): array
    {
        return [
            'monthly' => ['monthly'],
            'yearly' => ['yearly'],
            'one_time' => ['one_time'],
        ];
    }

    #[Test]
    public function FrequencyTypeRejectsInvalidValues(): void
    {
        $validator = $this->validate(['frequency_type' => 'daily']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_type', $validator->errors()->toArray());
    }

    #[Test]
    public function FrequencyIntervalMustBeAtLeast1(): void
    {
        $validator = $this->validate(['frequency_interval' => 0]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_interval', $validator->errors()->toArray());
    }

    #[Test]
    public function FrequencyIntervalCannotExceed100(): void
    {
        $validator = $this->validate(['frequency_interval' => 101]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_interval', $validator->errors()->toArray());
    }

    #[Test]
    public function StartDateMustBeValidDate(): void
    {
        $validator = $this->validate(['start_date' => 'not-a-date']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('start_date', $validator->errors()->toArray());
    }

    #[Test]
    public function EndDateMustBeValidDate(): void
    {
        $validator = $this->validate(['end_date' => 'not-a-date']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }

    #[Test]
    public function EndDateMustBeAfterOrEqualStartDate(): void
    {
        $validator = $this->validate([
            'start_date' => '2026-06-01',
            'end_date' => '2026-01-01',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }

    #[Test]
    public function NoteCannotExceed500Characters(): void
    {
        $validator = $this->validate(['note' => str_repeat('a', 501)]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('note', $validator->errors()->toArray());
    }

    #[Test]
    public function IsActiveMustBeBoolean(): void
    {
        $validator = $this->validate(['is_active' => 'not-a-boolean']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }

    #[Test]
    public function IsActiveAcceptsBooleanValues(): void
    {
        $validator = $this->validate(['is_active' => true]);
        $this->assertTrue($validator->passes());

        $validator = $this->validate(['is_active' => false]);
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new UpdateCashFlowItemRequest();

        $this->assertTrue($request->authorize());
    }
}
