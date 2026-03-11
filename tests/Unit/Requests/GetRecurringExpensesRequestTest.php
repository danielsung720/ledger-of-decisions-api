<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\GetRecurringExpensesRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetRecurringExpensesRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new GetRecurringExpensesRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function FiltersShouldNormalizeAndClampValues(): void
    {
        $request = GetRecurringExpensesRequest::create('/api/recurring-expenses', 'GET', [
            'category' => ' living ,food,,',
            'is_active' => 'true',
            'frequency_type' => [' monthly ', '', 'yearly'],
            'per_page' => 500,
        ]);

        $filters = $request->filters();

        $this->assertSame(['living', 'food'], $filters['category']);
        $this->assertTrue($filters['is_active']);
        $this->assertSame(['monthly', 'yearly'], $filters['frequency_type']);
        $this->assertSame(100, $filters['per_page']);
    }

    #[Test]
    public function ToDtoShouldMapValidatedValues(): void
    {
        $request = GetRecurringExpensesRequest::create('/api/recurring-expenses', 'GET', [
            'category' => 'living,invalid',
            'frequency_type' => 'monthly,invalid',
            'per_page' => 20,
        ]);

        $dto = $request->toDto();

        $this->assertSame(['living'], $dto->categories);
        $this->assertSame(['monthly'], $dto->frequencyTypes);
        $this->assertSame(20, $dto->perPage);
    }

    #[Test]
    public function RulesShouldRejectInvalidIsActiveValue(): void
    {
        $validator = $this->validate(['is_active' => 'abc']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new GetRecurringExpensesRequest();

        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function RulesShouldRejectInvalidCategoryValue(): void
    {
        $validator = $this->validate(['category' => 'living,invalid']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    public function RulesShouldRejectInvalidFrequencyTypeValue(): void
    {
        $validator = $this->validate(['frequency_type' => ['monthly', 'invalid']]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_type', $validator->errors()->toArray());
    }

    #[Test]
    public function RulesShouldRejectInvalidCategoryFormat(): void
    {
        $validator = $this->validate(['category' => true]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    public function RulesShouldRejectInvalidFrequencyTypeFormat(): void
    {
        $validator = $this->validate(['frequency_type' => 1]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_type', $validator->errors()->toArray());
    }

    #[Test]
    #[DataProvider('ValidIsActiveValues')]
    public function RulesShouldAcceptValidIsActiveValues(mixed $value): void
    {
        $validator = $this->validate(['is_active' => $value]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function FiltersShouldHandleNonScalarCategoryInputAsEmptyArray(): void
    {
        $request = GetRecurringExpensesRequest::create('/api/recurring-expenses', 'GET', [
            'category' => 1,
        ]);

        $filters = $request->filters();

        $this->assertSame([], $filters['category']);
        $this->assertSame(15, $filters['per_page']);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function ValidIsActiveValues(): array
    {
        return [
            'bool true' => [true],
            'bool false' => [false],
            'int 1' => [1],
            'int 0' => [0],
            'string true' => ['true'],
            'string false' => ['false'],
            'string 1' => ['1'],
            'string 0' => ['0'],
        ];
    }
}
