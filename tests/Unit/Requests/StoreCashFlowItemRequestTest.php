<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreCashFlowItemRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreCashFlowItemRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreCashFlowItemRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => '房租',
            'amount' => 15000,
            'category' => 'living',
            'frequency_type' => 'monthly',
            'start_date' => '2026-01-01',
        ], $overrides);
    }

    #[Test]
    public function ItPassesWithValidMinimalData(): void
    {
        $validator = $this->validate($this->validData());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function ItPassesWithAllFields(): void
    {
        $validator = $this->validate([
            'name' => '房租',
            'amount' => 15000,
            'currency' => 'TWD',
            'category' => 'living',
            'frequency_type' => 'monthly',
            'frequency_interval' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'note' => '每月固定支出',
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function NameIsRequired(): void
    {
        $validator = $this->validate($this->validData(['name' => null]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function NameCannotExceed255Characters(): void
    {
        $validator = $this->validate($this->validData(['name' => str_repeat('a', 256)]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountIsRequired(): void
    {
        $validator = $this->validate($this->validData(['amount' => null]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountMustBeNumeric(): void
    {
        $validator = $this->validate($this->validData(['amount' => 'not-a-number']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountCannotBeNegative(): void
    {
        $validator = $this->validate($this->validData(['amount' => -100]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function CurrencyIsOptional(): void
    {
        $validator = $this->validate($this->validData());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function CurrencyMustBe3Characters(): void
    {
        $validator = $this->validate($this->validData(['currency' => 'TWDD']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
    }

    #[Test]
    public function CategoryIsRequired(): void
    {
        $validator = $this->validate($this->validData(['category' => null]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    #[DataProvider('validCategoryProvider')]
    public function CategoryAcceptsValidValues(string $category): void
    {
        $validator = $this->validate($this->validData(['category' => $category]));

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
        $validator = $this->validate($this->validData(['category' => 'invalid']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    public function FrequencyTypeIsRequired(): void
    {
        $validator = $this->validate($this->validData(['frequency_type' => null]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_type', $validator->errors()->toArray());
    }

    #[Test]
    #[DataProvider('validFrequencyTypeProvider')]
    public function FrequencyTypeAcceptsValidValues(string $frequencyType): void
    {
        $validator = $this->validate($this->validData(['frequency_type' => $frequencyType]));

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
        $validator = $this->validate($this->validData(['frequency_type' => 'daily']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_type', $validator->errors()->toArray());
    }

    #[Test]
    public function FrequencyIntervalIsOptional(): void
    {
        $validator = $this->validate($this->validData());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function FrequencyIntervalMustBeAtLeast1(): void
    {
        $validator = $this->validate($this->validData(['frequency_interval' => 0]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_interval', $validator->errors()->toArray());
    }

    #[Test]
    public function FrequencyIntervalCannotExceed100(): void
    {
        $validator = $this->validate($this->validData(['frequency_interval' => 101]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_interval', $validator->errors()->toArray());
    }

    #[Test]
    public function StartDateIsRequired(): void
    {
        $validator = $this->validate($this->validData(['start_date' => null]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('start_date', $validator->errors()->toArray());
    }

    #[Test]
    public function StartDateMustBeValidDate(): void
    {
        $validator = $this->validate($this->validData(['start_date' => 'not-a-date']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('start_date', $validator->errors()->toArray());
    }

    #[Test]
    public function EndDateIsOptional(): void
    {
        $validator = $this->validate($this->validData(['end_date' => null]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function EndDateMustBeAfterOrEqualStartDate(): void
    {
        $validator = $this->validate($this->validData([
            'start_date' => '2026-06-01',
            'end_date' => '2026-01-01',
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }

    #[Test]
    public function NoteIsOptional(): void
    {
        $validator = $this->validate($this->validData(['note' => null]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function NoteCannotExceed500Characters(): void
    {
        $validator = $this->validate($this->validData(['note' => str_repeat('a', 501)]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('note', $validator->errors()->toArray());
    }

    #[Test]
    public function ItReturnsChineseErrorMessages(): void
    {
        $validator = $this->validate([]);

        $errors = $validator->errors()->toArray();

        $this->assertStringContainsString('請輸入項目名稱', $errors['name'][0]);
        $this->assertStringContainsString('請輸入金額', $errors['amount'][0]);
        $this->assertStringContainsString('請選擇消費類別', $errors['category'][0]);
        $this->assertStringContainsString('請選擇週期類型', $errors['frequency_type'][0]);
        $this->assertStringContainsString('請選擇開始日期', $errors['start_date'][0]);
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new StoreCashFlowItemRequest();

        $this->assertTrue($request->authorize());
    }
}
