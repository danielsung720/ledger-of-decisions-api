<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\GetRecurringExpenseHistoryRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetRecurringExpenseHistoryRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new GetRecurringExpenseHistoryRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function FiltersShouldClampLimitValue(): void
    {
        $request = GetRecurringExpenseHistoryRequest::create('/api/recurring-expenses/1/history', 'GET', [
            'limit' => 200,
        ]);

        $this->assertSame(100, $request->toDto()->limit);
    }

    #[Test]
    public function RulesShouldRejectInvalidLimit(): void
    {
        $validator = $this->validate(['limit' => 0]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('limit', $validator->errors()->toArray());
    }
}
