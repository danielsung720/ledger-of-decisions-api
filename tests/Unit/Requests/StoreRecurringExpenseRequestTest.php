<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreRecurringExpenseRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreRecurringExpenseRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreRecurringExpenseRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => '房租',
            'amount_min' => 15000,
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
            'amount_min' => 15000,
            'amount_max' => 20000,
            'currency' => 'TWD',
            'category' => 'living',
            'frequency_type' => 'monthly',
            'frequency_interval' => 1,
            'day_of_month' => 5,
            'month_of_year' => null,
            'day_of_week' => null,
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'default_intent' => 'necessity',
            'note' => '每月房租',
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
    public function NameCanBe255Characters(): void
    {
        $validator = $this->validate($this->validData(['name' => str_repeat('a', 255)]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function AmountMinIsRequired(): void
    {
        $validator = $this->validate($this->validData(['amount_min' => null]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount_min', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountMinMustBeNumeric(): void
    {
        $validator = $this->validate($this->validData(['amount_min' => 'not-a-number']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount_min', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountMinCannotBeNegative(): void
    {
        $validator = $this->validate($this->validData(['amount_min' => -100]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount_min', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountMinCanBeZero(): void
    {
        $validator = $this->validate($this->validData(['amount_min' => 0]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function AmountMaxIsOptional(): void
    {
        $validator = $this->validate($this->validData(['amount_max' => null]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function AmountMaxMustBeNumeric(): void
    {
        $validator = $this->validate($this->validData(['amount_max' => 'not-a-number']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount_max', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountMaxMustBeGteAmountMin(): void
    {
        $validator = $this->validate($this->validData([
            'amount_min' => 1000,
            'amount_max' => 500,
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount_max', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountMaxCanEqualAmountMin(): void
    {
        $validator = $this->validate($this->validData([
            'amount_min' => 1000,
            'amount_max' => 1000,
        ]));

        $this->assertTrue($validator->passes());
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
            'daily' => ['daily'],
            'weekly' => ['weekly'],
            'monthly' => ['monthly'],
            'yearly' => ['yearly'],
        ];
    }

    #[Test]
    public function FrequencyTypeRejectsInvalidValues(): void
    {
        $validator = $this->validate($this->validData(['frequency_type' => 'hourly']));

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
    public function FrequencyIntervalCanBe100(): void
    {
        $validator = $this->validate($this->validData(['frequency_interval' => 100]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function DayOfMonthIsOptional(): void
    {
        $validator = $this->validate($this->validData(['day_of_month' => null]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function DayOfMonthMustBeBetween1And31(): void
    {
        $validator = $this->validate($this->validData(['day_of_month' => 0]));
        $this->assertTrue($validator->fails());

        $validator = $this->validate($this->validData(['day_of_month' => 32]));
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function DayOfMonthAcceptsValidRange(): void
    {
        $validator = $this->validate($this->validData(['day_of_month' => 1]));
        $this->assertTrue($validator->passes());

        $validator = $this->validate($this->validData(['day_of_month' => 31]));
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function MonthOfYearIsOptional(): void
    {
        $validator = $this->validate($this->validData(['month_of_year' => null]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function MonthOfYearMustBeBetween1And12(): void
    {
        $validator = $this->validate($this->validData(['month_of_year' => 0]));
        $this->assertTrue($validator->fails());

        $validator = $this->validate($this->validData(['month_of_year' => 13]));
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function DayOfWeekIsOptional(): void
    {
        $validator = $this->validate($this->validData(['day_of_week' => null]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function DayOfWeekMustBeBetween0And6(): void
    {
        $validator = $this->validate($this->validData(['day_of_week' => -1]));
        $this->assertTrue($validator->fails());

        $validator = $this->validate($this->validData(['day_of_week' => 7]));
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function DayOfWeekAcceptsValidRange(): void
    {
        $validator = $this->validate($this->validData(['day_of_week' => 0]));
        $this->assertTrue($validator->passes());

        $validator = $this->validate($this->validData(['day_of_week' => 6]));
        $this->assertTrue($validator->passes());
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
    public function EndDateCanEqualStartDate(): void
    {
        $validator = $this->validate($this->validData([
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-01',
        ]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function DefaultIntentIsOptional(): void
    {
        $validator = $this->validate($this->validData(['default_intent' => null]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[DataProvider('validIntentProvider')]
    public function DefaultIntentAcceptsValidValues(string $intent): void
    {
        $validator = $this->validate($this->validData(['default_intent' => $intent]));

        $this->assertTrue($validator->passes());
    }

    public static function validIntentProvider(): array
    {
        return [
            'necessity' => ['necessity'],
            'efficiency' => ['efficiency'],
            'enjoyment' => ['enjoyment'],
            'recovery' => ['recovery'],
            'impulse' => ['impulse'],
        ];
    }

    #[Test]
    public function DefaultIntentRejectsInvalidValues(): void
    {
        $validator = $this->validate($this->validData(['default_intent' => 'invalid']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('default_intent', $validator->errors()->toArray());
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

        $this->assertStringContainsString('請輸入固定支出名稱', $errors['name'][0]);
        $this->assertStringContainsString('請輸入金額', $errors['amount_min'][0]);
        $this->assertStringContainsString('請選擇消費類別', $errors['category'][0]);
        $this->assertStringContainsString('請選擇週期類型', $errors['frequency_type'][0]);
        $this->assertStringContainsString('請選擇開始日期', $errors['start_date'][0]);
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new StoreRecurringExpenseRequest();

        $this->assertTrue($request->authorize());
    }
}
