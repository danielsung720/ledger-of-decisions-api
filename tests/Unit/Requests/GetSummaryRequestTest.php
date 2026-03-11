<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\GetSummaryRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetSummaryRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new GetSummaryRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function ItPassesWithEmptyData(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function ItUsesSameValidationRulesAsGetIntentsRequest(): void
    {
        $validator = $this->validate([
            'start_date' => '2026-02-28',
            'end_date' => '2026-02-01',
            'preset' => 'invalid',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('start_date', $validator->errors()->toArray());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
        $this->assertArrayHasKey('preset', $validator->errors()->toArray());
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new GetSummaryRequest();

        $this->assertTrue($request->authorize());
    }
}
