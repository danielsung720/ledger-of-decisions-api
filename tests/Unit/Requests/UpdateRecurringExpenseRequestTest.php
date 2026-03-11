<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateRecurringExpenseRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateRecurringExpenseRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new UpdateRecurringExpenseRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function it_passes_with_empty_data(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function it_passes_with_partial_update(): void
    {
        $validator = $this->validate(['name' => '新名稱']);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function name_cannot_exceed255_characters(): void
    {
        $validator = $this->validate(['name' => str_repeat('a', 256)]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function amount_min_must_be_numeric(): void
    {
        $validator = $this->validate(['amount_min' => 'not-a-number']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount_min', $validator->errors()->toArray());
    }

    #[Test]
    public function amount_min_cannot_be_negative(): void
    {
        $validator = $this->validate(['amount_min' => -100]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount_min', $validator->errors()->toArray());
    }

    #[Test]
    public function amount_max_must_be_numeric(): void
    {
        $validator = $this->validate(['amount_max' => 'not-a-number']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount_max', $validator->errors()->toArray());
    }

    #[Test]
    public function currency_must_be3_characters(): void
    {
        $validator = $this->validate(['currency' => 'TWDD']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
    }

    #[Test]
    #[DataProvider('validCategoryProvider')]
    public function category_accepts_valid_values(string $category): void
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
    public function category_rejects_invalid_values(): void
    {
        $validator = $this->validate(['category' => 'invalid']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    #[DataProvider('validFrequencyTypeProvider')]
    public function frequency_type_accepts_valid_values(string $frequencyType): void
    {
        $validator = $this->validate(['frequency_type' => $frequencyType]);

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
    public function frequency_type_rejects_invalid_values(): void
    {
        $validator = $this->validate(['frequency_type' => 'hourly']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_type', $validator->errors()->toArray());
    }

    #[Test]
    public function frequency_interval_must_be_at_least1(): void
    {
        $validator = $this->validate(['frequency_interval' => 0]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_interval', $validator->errors()->toArray());
    }

    #[Test]
    public function frequency_interval_cannot_exceed100(): void
    {
        $validator = $this->validate(['frequency_interval' => 101]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_interval', $validator->errors()->toArray());
    }

    #[Test]
    public function day_of_month_must_be_between1_and31(): void
    {
        $validator = $this->validate(['day_of_month' => 0]);
        $this->assertTrue($validator->fails());

        $validator = $this->validate(['day_of_month' => 32]);
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function month_of_year_must_be_between1_and12(): void
    {
        $validator = $this->validate(['month_of_year' => 0]);
        $this->assertTrue($validator->fails());

        $validator = $this->validate(['month_of_year' => 13]);
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function day_of_week_must_be_between0_and6(): void
    {
        $validator = $this->validate(['day_of_week' => -1]);
        $this->assertTrue($validator->fails());

        $validator = $this->validate(['day_of_week' => 7]);
        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function start_date_must_be_valid_date(): void
    {
        $validator = $this->validate(['start_date' => 'not-a-date']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('start_date', $validator->errors()->toArray());
    }

    #[Test]
    public function end_date_must_be_valid_date(): void
    {
        $validator = $this->validate(['end_date' => 'not-a-date']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }

    #[Test]
    #[DataProvider('validIntentProvider')]
    public function default_intent_accepts_valid_values(string $intent): void
    {
        $validator = $this->validate(['default_intent' => $intent]);

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
    public function default_intent_rejects_invalid_values(): void
    {
        $validator = $this->validate(['default_intent' => 'invalid']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('default_intent', $validator->errors()->toArray());
    }

    #[Test]
    public function note_cannot_exceed500_characters(): void
    {
        $validator = $this->validate(['note' => str_repeat('a', 501)]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('note', $validator->errors()->toArray());
    }

    #[Test]
    public function is_active_must_be_boolean(): void
    {
        $validator = $this->validate(['is_active' => 'not-a-boolean']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }

    #[Test]
    public function is_active_accepts_boolean_values(): void
    {
        $validator = $this->validate(['is_active' => true]);
        $this->assertTrue($validator->passes());

        $validator = $this->validate(['is_active' => false]);
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function amount_max_must_be_greater_than_or_equal_to_amount_min_when_both_provided(): void
    {
        $validator = $this->validate([
            'amount_min' => 2000,
            'amount_max' => 1000,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount_max', $validator->errors()->toArray());
    }

    #[Test]
    public function end_date_must_be_after_or_equal_to_start_date_when_both_provided(): void
    {
        $validator = $this->validate([
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-09',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }

    #[Test]
    public function request_is_authorized(): void
    {
        $request = new UpdateRecurringExpenseRequest;

        $this->assertTrue($request->authorize());
    }
}
