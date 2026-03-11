<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\GetCashFlowProjectionRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetCashFlowProjectionRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new GetCashFlowProjectionRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function FiltersShouldClampMonthsWithinOneToTwelve(): void
    {
        $high = GetCashFlowProjectionRequest::create('/api/cash-flow/projection', 'GET', ['months' => 99]);
        $low = GetCashFlowProjectionRequest::create('/api/cash-flow/projection', 'GET', ['months' => 0]);
        $default = GetCashFlowProjectionRequest::create('/api/cash-flow/projection', 'GET');

        $this->assertSame(12, $high->filters()['months']);
        $this->assertSame(1, $low->filters()['months']);
        $this->assertSame(1, $default->filters()['months']);
    }

    #[Test]
    public function ToDtoShouldMapNormalizedMonths(): void
    {
        $request = GetCashFlowProjectionRequest::create('/api/cash-flow/projection', 'GET', ['months' => 24]);

        $dto = $request->toDto();

        $this->assertSame(12, $dto->months);
    }

    #[Test]
    public function RulesShouldRejectNonIntegerMonths(): void
    {
        $validator = $this->validate(['months' => 'abc']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('months', $validator->errors()->toArray());
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new GetCashFlowProjectionRequest();

        $this->assertTrue($request->authorize());
    }
}
