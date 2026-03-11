<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Enums\DatePreset;
use App\Http\Requests\GetExpensesRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetExpensesRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new GetExpensesRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function ItPassesWithEmptyData(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function CategoryAcceptsSingleValueOrCommaSeparatedOrArray(): void
    {
        $this->assertTrue($this->validate(['category' => 'food'])->passes());
        $this->assertTrue($this->validate(['category' => 'food,transport'])->passes());
        $this->assertTrue($this->validate(['category' => ['food', 'transport']])->passes());
    }

    #[Test]
    public function CategoryRejectsInvalidValue(): void
    {
        $validator = $this->validate(['category' => 'invalid']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    public function IntentAcceptsSingleValueOrCommaSeparatedOrArray(): void
    {
        $this->assertTrue($this->validate(['intent' => 'necessity'])->passes());
        $this->assertTrue($this->validate(['intent' => 'necessity,impulse'])->passes());
        $this->assertTrue($this->validate(['intent' => ['necessity', 'impulse']])->passes());
    }

    #[Test]
    public function IntentRejectsInvalidValue(): void
    {
        $validator = $this->validate(['intent' => 'invalid']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('intent', $validator->errors()->toArray());
    }

    #[Test]
    public function ConfidenceLevelAcceptsSingleValueOrCommaSeparatedOrArray(): void
    {
        $this->assertTrue($this->validate(['confidence_level' => 'high'])->passes());
        $this->assertTrue($this->validate(['confidence_level' => 'high,medium'])->passes());
        $this->assertTrue($this->validate(['confidence_level' => ['high', 'medium']])->passes());
    }

    #[Test]
    public function ConfidenceLevelRejectsInvalidValue(): void
    {
        $validator = $this->validate(['confidence_level' => 'invalid']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('confidence_level', $validator->errors()->toArray());
    }

    #[Test]
    public function CategoryRejectsNonStringAndNonArrayInput(): void
    {
        $validator = $this->validate(['category' => 123]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    public function IntentRejectsNonStringAndNonArrayInput(): void
    {
        $validator = $this->validate(['intent' => 123]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('intent', $validator->errors()->toArray());
    }

    #[Test]
    public function ConfidenceLevelRejectsNonStringAndNonArrayInput(): void
    {
        $validator = $this->validate(['confidence_level' => 123]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('confidence_level', $validator->errors()->toArray());
    }

    #[Test]
    public function DateRangeValidationWorks(): void
    {
        $this->assertTrue($this->validate([
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
        ])->passes());

        $validator = $this->validate([
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-01',
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }

    #[Test]
    public function PresetValidationWorks(): void
    {
        $this->assertTrue($this->validate(['preset' => DatePreset::Today->value])->passes());

        $validator = $this->validate(['preset' => 'invalid']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('preset', $validator->errors()->toArray());
    }

    #[Test]
    public function PerPageMustBeAtLeast1(): void
    {
        $validator = $this->validate(['per_page' => 1]);
        $this->assertTrue($validator->passes());

        $validator = $this->validate(['per_page' => 0]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->toArray());
    }

    #[Test]
    public function FiltersShouldNormalizeQueryValuesAndClampPerPage(): void
    {
        $request = GetExpensesRequest::create('/api/expenses', 'GET', [
            'category' => ' food ,transport,, ',
            'intent' => [' necessity ', '', 'impulse'],
            'confidence_level' => 'high, medium',
            'per_page' => 500,
            'preset' => DatePreset::ThisMonth->value,
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
        ]);

        $filters = $request->filters();

        $this->assertSame(['food', 'transport'], $filters['category']);
        $this->assertSame(['necessity', 'impulse'], $filters['intent']);
        $this->assertSame(['high', 'medium'], $filters['confidence_level']);
        $this->assertSame(100, $filters['per_page']);
        $this->assertSame(DatePreset::ThisMonth->value, $filters['preset']);
        $this->assertSame('2026-02-01', $filters['start_date']);
        $this->assertSame('2026-02-28', $filters['end_date']);
    }

    #[Test]
    public function ToDtoShouldMapNormalizedFilters(): void
    {
        $request = GetExpensesRequest::create('/api/expenses', 'GET', [
            'category' => 'food,invalid',
            'intent' => ['necessity', 'invalid'],
            'confidence_level' => 'high,invalid',
            'preset' => DatePreset::ThisWeek->value,
            'per_page' => 20,
        ]);

        $dto = $request->toDto();

        $this->assertSame(['food'], $dto->categories);
        $this->assertSame(['necessity'], $dto->intents);
        $this->assertSame(['high'], $dto->confidenceLevels);
        $this->assertSame(DatePreset::ThisWeek, $dto->preset);
        $this->assertSame(20, $dto->perPage);
    }

    #[Test]
    public function FiltersShouldReturnEmptyArraysForNonStringAndNonArrayQueryValues(): void
    {
        $request = GetExpensesRequest::create('/api/expenses', 'GET', [
            'category' => 123,
            'intent' => 456,
            'confidence_level' => 789,
        ]);

        $filters = $request->filters();

        $this->assertSame([], $filters['category']);
        $this->assertSame([], $filters['intent']);
        $this->assertSame([], $filters['confidence_level']);
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new GetExpensesRequest();

        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function RulesShouldContainExpectedKeys(): void
    {
        $request = new GetExpensesRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('start_date', $rules);
        $this->assertArrayHasKey('end_date', $rules);
        $this->assertArrayHasKey('preset', $rules);
        $this->assertArrayHasKey('category', $rules);
        $this->assertArrayHasKey('intent', $rules);
        $this->assertArrayHasKey('confidence_level', $rules);
        $this->assertArrayHasKey('per_page', $rules);
    }

    #[Test]
    public function MessagesShouldContainExpectedValidationMessages(): void
    {
        $request = new GetExpensesRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('start_date.date', $messages);
        $this->assertArrayHasKey('end_date.date', $messages);
        $this->assertArrayHasKey('end_date.after_or_equal', $messages);
        $this->assertArrayHasKey('preset.in', $messages);
        $this->assertArrayHasKey('per_page.integer', $messages);
        $this->assertArrayHasKey('per_page.min', $messages);
    }
}
