<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\GetTrendsRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetTrendsRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new GetTrendsRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function it_passes_with_empty_data(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function it_passes_with_arbitrary_query_data_because_no_rules_defined(): void
    {
        $validator = $this->validate([
            'foo' => 'bar',
            'start_date' => 'not-a-date',
            'any_value' => 123,
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function request_is_authorized(): void
    {
        $request = new GetTrendsRequest();

        $this->assertTrue($request->authorize());
    }
}
