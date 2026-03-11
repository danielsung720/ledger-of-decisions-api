<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\GenerateExpenseRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateExpenseRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new GenerateExpenseRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function ItPassesWithEmptyData(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function ItPassesWithAllFields(): void
    {
        $validator = $this->validate([
            'date' => '2026-02-13',
            'amount' => 1000,
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function DateIsOptional(): void
    {
        $validator = $this->validate(['amount' => 1000]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function DateMustBeValidDate(): void
    {
        $validator = $this->validate(['date' => 'not-a-date']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
    }

    #[Test]
    public function DateCannotBeFuture(): void
    {
        $validator = $this->validate(['date' => '2099-12-31']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
    }

    #[Test]
    public function DateCanBeToday(): void
    {
        $validator = $this->validate(['date' => now()->toDateString()]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function DateCanBePast(): void
    {
        $validator = $this->validate(['date' => '2020-01-01']);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function AmountIsOptional(): void
    {
        $validator = $this->validate(['date' => '2026-02-13']);

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
    public function AmountCannotExceed10Million(): void
    {
        $validator = $this->validate(['amount' => 10000001]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountCanBe10Million(): void
    {
        $validator = $this->validate(['amount' => 10000000]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function ItReturnsChineseErrorMessages(): void
    {
        $validator = $this->validate([
            'date' => '2099-12-31',
            'amount' => 'invalid',
        ]);

        $errors = $validator->errors()->toArray();

        $this->assertStringContainsString('日期不能是未來日期', $errors['date'][0]);
        $this->assertStringContainsString('金額必須是數字', $errors['amount'][0]);
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new GenerateExpenseRequest();

        $this->assertTrue($request->authorize());
    }
}
