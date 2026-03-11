<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\GetCashFlowItemsRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetCashFlowItemsRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new GetCashFlowItemsRequest();

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
        $this->assertTrue($this->validate(['category' => 'living'])->passes());
        $this->assertTrue($this->validate(['category' => 'living,food'])->passes());
        $this->assertTrue($this->validate(['category' => ['living', 'food']])->passes());
    }

    #[Test]
    public function CategoryRejectsInvalidValue(): void
    {
        $validator = $this->validate(['category' => 'invalid_category']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    public function IsActiveAcceptsBooleanValues(): void
    {
        $this->assertTrue($this->validate(['is_active' => true])->passes());
        $this->assertTrue($this->validate(['is_active' => false])->passes());
        $this->assertTrue($this->validate(['is_active' => 1])->passes());
        $this->assertTrue($this->validate(['is_active' => 0])->passes());
        $this->assertTrue($this->validate(['is_active' => '1'])->passes());
        $this->assertTrue($this->validate(['is_active' => '0'])->passes());
        $this->assertTrue($this->validate(['is_active' => 'true'])->passes());
        $this->assertTrue($this->validate(['is_active' => 'false'])->passes());
    }

    #[Test]
    public function IsActiveRejectsInvalidValue(): void
    {
        $validator = $this->validate(['is_active' => 'abc']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }

    #[Test]
    public function FrequencyTypeAcceptsSingleValueOrCommaSeparatedOrArray(): void
    {
        $this->assertTrue($this->validate(['frequency_type' => 'monthly'])->passes());
        $this->assertTrue($this->validate(['frequency_type' => 'monthly,yearly'])->passes());
        $this->assertTrue($this->validate(['frequency_type' => ['monthly', 'yearly']])->passes());
    }

    #[Test]
    public function FrequencyTypeRejectsInvalidValue(): void
    {
        $validator = $this->validate(['frequency_type' => 'daily']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_type', $validator->errors()->toArray());
    }

    #[Test]
    public function CategoryRejectsNonStringAndNonArrayInput(): void
    {
        $validator = $this->validate(['category' => 123]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    public function FrequencyTypeRejectsNonStringAndNonArrayInput(): void
    {
        $validator = $this->validate(['frequency_type' => 123]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_type', $validator->errors()->toArray());
    }

    #[Test]
    public function PerPageMustBeAtLeast1(): void
    {
        $validator = $this->validate(['per_page' => 0]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->toArray());
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new GetCashFlowItemsRequest();

        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function FiltersShouldNormalizeQueryValuesAndClampPerPage(): void
    {
        $request = GetCashFlowItemsRequest::create('/api/cash-flow-items', 'GET', [
            'category' => ' living ,food,,',
            'is_active' => '1',
            'frequency_type' => ' monthly,yearly,,',
            'per_page' => 1000,
        ]);

        $filters = $request->filters();

        $this->assertSame(['living', 'food'], $filters['category']);
        $this->assertTrue($filters['is_active']);
        $this->assertSame(['monthly', 'yearly'], $filters['frequency_type']);
        $this->assertSame(100, $filters['per_page']);
    }

    #[Test]
    public function FiltersShouldNormalizeArrayValuesAndUseDefaults(): void
    {
        $request = GetCashFlowItemsRequest::create('/api/cash-flow-items', 'GET', [
            'category' => [' living ', '', 'food'],
            'frequency_type' => [' monthly ', '', 'yearly'],
        ]);

        $filters = $request->filters();

        $this->assertSame(['living', 'food'], $filters['category']);
        $this->assertSame(['monthly', 'yearly'], $filters['frequency_type']);
        $this->assertSame(15, $filters['per_page']);
        $this->assertArrayNotHasKey('is_active', $filters);
    }

    #[Test]
    public function FiltersShouldReturnEmptyArraysForNonStringAndNonArrayQueryValues(): void
    {
        $request = GetCashFlowItemsRequest::create('/api/cash-flow-items', 'GET', [
            'category' => 123,
            'frequency_type' => 456,
        ]);

        $filters = $request->filters();

        $this->assertSame([], $filters['category']);
        $this->assertSame([], $filters['frequency_type']);
    }

    #[Test]
    public function ToDtoShouldMapNormalizedFilters(): void
    {
        $request = GetCashFlowItemsRequest::create('/api/cash-flow-items', 'GET', [
            'category' => ['living', 'food', 'invalid'],
            'is_active' => 'false',
            'frequency_type' => ['monthly', 'yearly', 'invalid'],
            'per_page' => 20,
        ]);

        $dto = $request->toDto();

        $this->assertSame(['living', 'food'], $dto->categories);
        $this->assertFalse($dto->isActive);
        $this->assertSame(['monthly', 'yearly'], $dto->frequencyTypes);
        $this->assertSame(20, $dto->perPage);
    }

    #[Test]
    public function RulesShouldContainExpectedKeys(): void
    {
        $request = new GetCashFlowItemsRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('category', $rules);
        $this->assertArrayHasKey('is_active', $rules);
        $this->assertArrayHasKey('frequency_type', $rules);
        $this->assertArrayHasKey('per_page', $rules);
    }

    #[Test]
    public function MessagesShouldDefinePerPageValidationMessages(): void
    {
        $request = new GetCashFlowItemsRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('per_page.integer', $messages);
        $this->assertArrayHasKey('per_page.min', $messages);
    }
}
