<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\GetIncomesRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetIncomesRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new GetIncomesRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function ItPassesWithEmptyData(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function IsActiveAcceptsBooleanValues(): void
    {
        $validator = $this->validate(['is_active' => 'true']);
        $this->assertTrue($validator->passes());

        $validator = $this->validate(['is_active' => 'false']);
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function IsActiveAcceptsNativeBooleanAndIntegerValues(): void
    {
        $validator = $this->validate(['is_active' => true]);
        $this->assertTrue($validator->passes());

        $validator = $this->validate(['is_active' => 1]);
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function IsActiveRejectsInvalidValues(): void
    {
        $validator = $this->validate(['is_active' => 'invalid']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('is_active', $validator->errors()->toArray());
    }

    #[Test]
    public function PerPageMustBeAtLeast1(): void
    {
        $validator = $this->validate(['per_page' => 1]);
        $this->assertTrue($validator->passes());

        $validator = $this->validate(['per_page' => 0]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->toArray());

        $validator = $this->validate(['per_page' => 101]);
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function FrequencyTypeAcceptsSingleString(): void
    {
        $validator = $this->validate(['frequency_type' => 'monthly']);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function FrequencyTypeAcceptsCommaSeparatedString(): void
    {
        $validator = $this->validate(['frequency_type' => 'monthly,yearly,one_time']);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function FrequencyTypeAcceptsArray(): void
    {
        $validator = $this->validate(['frequency_type' => ['monthly', 'yearly']]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function FrequencyTypeRejectsInvalidTypeValue(): void
    {
        $validator = $this->validate(['frequency_type' => 'monthly,daily']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_type', $validator->errors()->toArray());
    }

    #[Test]
    public function FrequencyTypeRejectsNonStringAndNonArrayInput(): void
    {
        $validator = $this->validate(['frequency_type' => 123]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frequency_type', $validator->errors()->toArray());
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new GetIncomesRequest();

        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function FiltersShouldNormalizeQueryValuesAndClampPerPage(): void
    {
        $request = GetIncomesRequest::create('/api/incomes', 'GET', [
            'is_active' => 'true',
            'frequency_type' => ' monthly ,yearly,, ',
            'per_page' => 500,
        ]);

        $filters = $request->filters();

        $this->assertTrue($filters['is_active']);
        $this->assertSame(['monthly', 'yearly'], $filters['frequency_type']);
        $this->assertSame(100, $filters['per_page']);
    }

    #[Test]
    public function ToDtoShouldMapNormalizedFilters(): void
    {
        $request = GetIncomesRequest::create('/api/incomes', 'GET', [
            'is_active' => 'false',
            'frequency_type' => 'monthly,invalid',
            'per_page' => 20,
        ]);

        $dto = $request->toDto();

        $this->assertFalse($dto->isActive);
        $this->assertSame(['monthly'], $dto->frequencyTypes);
        $this->assertSame(20, $dto->perPage);
    }

    #[Test]
    public function FiltersShouldNormalizeArrayFrequencyTypeValues(): void
    {
        $request = GetIncomesRequest::create('/api/incomes', 'GET', [
            'frequency_type' => [' monthly ', '', 'yearly'],
        ]);

        $filters = $request->filters();

        $this->assertSame(['monthly', 'yearly'], $filters['frequency_type']);
    }

    #[Test]
    public function FiltersShouldReturnEmptyFrequencyTypeForNonStringNonArrayInput(): void
    {
        $request = GetIncomesRequest::create('/api/incomes', 'GET', [
            'frequency_type' => 123,
        ]);

        $filters = $request->filters();

        $this->assertSame([], $filters['frequency_type']);
    }

    #[Test]
    public function FiltersShouldUseDefaultPerPageWhenMissing(): void
    {
        $request = GetIncomesRequest::create('/api/incomes', 'GET', []);

        $filters = $request->filters();

        $this->assertSame(15, $filters['per_page']);
        $this->assertArrayNotHasKey('is_active', $filters);
        $this->assertArrayNotHasKey('frequency_type', $filters);
    }

    #[Test]
    public function FiltersShouldRespectConfigurablePerPageBounds(): void
    {
        Config::set('pagination.default_per_page', 20);
        Config::set('pagination.max_per_page', 50);

        $defaultRequest = GetIncomesRequest::create('/api/incomes', 'GET', []);
        $defaultFilters = $defaultRequest->filters();
        $this->assertSame(20, $defaultFilters['per_page']);

        $clampedRequest = GetIncomesRequest::create('/api/incomes', 'GET', ['per_page' => 500]);
        $clampedFilters = $clampedRequest->filters();
        $this->assertSame(50, $clampedFilters['per_page']);
    }

    #[Test]
    public function ToDtoShouldHandleMissingOptionalFilters(): void
    {
        $request = GetIncomesRequest::create('/api/incomes', 'GET', []);

        $dto = $request->toDto();

        $this->assertNull($dto->isActive);
        $this->assertSame([], $dto->frequencyTypes);
        $this->assertSame(15, $dto->perPage);
    }

    #[Test]
    public function MessagesShouldDefinePerPageValidationMessages(): void
    {
        $request = new GetIncomesRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('per_page.integer', $messages);
        $this->assertArrayHasKey('per_page.min', $messages);
    }

    #[Test]
    public function RulesShouldContainExpectedKeys(): void
    {
        $request = new GetIncomesRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('is_active', $rules);
        $this->assertArrayHasKey('per_page', $rules);
        $this->assertArrayHasKey('frequency_type', $rules);
    }
}
