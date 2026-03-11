<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\GetUpcomingRecurringExpensesRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetUpcomingRecurringExpensesRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new GetUpcomingRecurringExpensesRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function filters_should_clamp_days_value(): void
    {
        $request = GetUpcomingRecurringExpensesRequest::create('/api/recurring-expenses/upcoming', 'GET', [
            'days' => 500,
        ]);

        $dto = $request->toDto(1);

        $this->assertSame([1], $dto->scope->userIds());
        $this->assertSame(365, $dto->days);
    }

    #[Test]
    public function rules_should_reject_invalid_days(): void
    {
        $validator = $this->validate(['days' => 0]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('days', $validator->errors()->toArray());
    }
}
